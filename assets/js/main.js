const subjectInputSelector = "[name=subject]";
const contextInputSelector = "input#content_context";
const generateFormSelector = "form#generate_form";
const promptModalSelector = "#ai_content_prompt_modal";
const viewReportModal = "#report_modal";
const suggestionsSelector = "#suggestions";
const contentRegenerateSelector = ".regenerate";
const templateInputSelector =
	"#classic-builder-form textarea[name=plain],#classic-builder-form textarea[name=html]";

//loading state object for various task
const aiContentState = {
	subject_score: false,
	spam_score: false,
	suggestions: false,
};

//method for managing the loading states.
const toggleLoadingState = (key, error = "") => {
	if (aiContentState[key]) {
		aiContentState[key] = false;
		$("#" + key).removeClass("loading");
		if (!error.length)
			$("[data-deps=" + key + "]").removeClass("invisible");
	} else {
		aiContentState[key] = true;
		$("#" + key).addClass("loading");
		if (!error.length) $("[data-deps=" + key + "]").addClass("invisible");
	}

	if (error.length) errorToastr(error);
};

const errorToastr = (message) => {
	notify("error", "Error", message);
};

/**
 * Function to check subject line score for campign
 * @returns void
 */
const checkSubjectScore = async () => {
	let stateSelector = "subject_score";

	let subject = $(subjectInputSelector).val();
	if (!subject.length || aiContentState.subject_score) return;

	//lock state
	toggleLoadingState(stateSelector);

	const resp = await customHttpRequest(
		window.AICONTENT_SUBJECT_SCORE_URL,
		"POST",
		{
			subject,
		}
	);

	//update state
	toggleLoadingState(stateSelector, resp.message);

	//show result grade overview
	if (resp.full_html) {
		$("#" + stateSelector).html(resp.preview_html);
		$(viewReportModal + " iframe").attr("srcdoc", resp.full_html);
	}
};

/**
 * This function generates content using an AI content generator API
 * and updates the UI accordingly.
 * */
const generateContent = async () => {
	const suggestionsStateSelector = "suggestions";

	let context = $(contextInputSelector).val();

	// If suggestions are already present in the state, exit the function.
	if (aiContentState.suggestions) {
		return;
	}

	// If context is empty, show prompt modal and exit the function.
	if (!context.length) {
		$(promptModalSelector).modal("show");
		return;
	}

	// Lock state and show loading spinner.
	$("." + suggestionsStateSelector).removeClass("hidden");
	toggleLoadingState(suggestionsStateSelector);

	let contentType = "html";
	if (
		window.AICONTENT_CONTENT_GENERATOR_TYPE == "template" &&
		$(templateInputSelector).length
	) {
		contentType = $(templateInputSelector).attr("name");
	}

	//support plain text editor
	if (contentType == "plain") {
		context = context + ". The email should be plain text without html";
	}

	// Send HTTP POST request to AI content generator API.
	const resp = await customHttpRequest(
		window.AICONTENT_CONTENT_GENERATOR_URL +
			"/" +
			window.AICONTENT_CONTENT_GENERATOR_TYPE,
		"POST",
		{
			context,
		}
	);

	// Unlock state and hide loading spinner.
	toggleLoadingState(suggestionsStateSelector);

	// If an error message is returned, show it in a toastr error message.
	if (resp.message) {
		$("." + suggestionsStateSelector).addClass("hidden");
		return errorToastr(resp.message);
	}

	aiCacheContext(context);

	// If AI content generator type is 'subject', update suggestions list.
	if (window.AICONTENT_CONTENT_GENERATOR_TYPE == "subject") {
		let html = "";
		for (let i = 0; i < resp.suggestions.length; i++) {
			const text = resp.suggestions[i];
			html += `<li><a href='javascript:;'>${text}</a></li>`;
		}

		// Update UI for list of selection.
		$(`#${suggestionsStateSelector} ol`).html(html).slideUp().slideDown();

		// Click first item to trigger change on the input.
		$($(`#${suggestionsStateSelector} li a:first-of-type`)[0]).click();
	}

	// If AI content generator type is 'template', update active template box.
	if (window.AICONTENT_CONTENT_GENERATOR_TYPE == "template") {
		$(templateInputSelector)
			.text(resp.suggestions[0])
			.val(resp.suggestions[0]);

		if (tinymce?.activeEditor)
			tinymce.activeEditor.setContent(resp.suggestions[0]);
	}
};

const aiCacheContext = (context = "") => {
	const cid = window.location.href.split("campaigns/")?.[1]?.split("/")?.[0];

	if (!cid?.length) return;

	if (!context.length) {
		let cached = localStorage.getItem(cid);
		if (cached) $(contextInputSelector).val(cached);
		return;
	}
	localStorage.setItem(cid, context);
};

/**
 * Send test email
 *
 * @param {string} email Email to send the test to
 * @param {string} campaignUid The Campaign uid
 */
const sendTestEmail = async (email, campaignUid) => {
	// send email test request
	let sendTest = await customHttpRequest(
		window.AICONTENT_CAMPAIGN_TEST_URL,
		"POST",
		{
			uid: campaignUid,
			email,
		}
	);

	if (sendTest.status !== "success")
		throw new Error(
			_ai_lang.error_saving_form.replace("%s", "test") +
				": " +
				sendTest?.message
		);
};

/**
 * The function check spam score for campaign template. Works for campaign editor only
 * @returns
 */
const checkSpamScore = async () => {
	let stateSelector = "spam_score";
	const scoreResultSelector = "#" + stateSelector + " strong";

	// Get the active template and the test email. test email is set to container from backend
	let template = $(templateInputSelector).val();
	let email = $("[data-customer-spam-test-email]").data(
		"customer-spam-test-email"
	);

	// Check if the state is already set
	if (aiContentState.spam_score) return;

	// Check if customer email is available
	if (!email.length) {
		errorToastr(_ai_lang.spam_test_email_not_found);
		return;
	}

	// Check if template is available
	if (!template.length) {
		errorToastr(_ai_lang.empty_content);
		return;
	}

	// Lock the state
	toggleLoadingState(stateSelector);

	// Save the template form and insert email in the test form
	try {
		let campaignUid = "";

		let mainFormId = $(templateInputSelector).closest("form").attr("id");

		if (mainFormId.length)
			campaignUid = $("#" + mainFormId)
				.attr("action")
				.split("campaigns/")[1]
				.split("/")[0];

		if (!mainFormId.length || !campaignUid.length)
			throw new Error(_ai_lang.template_form_not_found);

		let saveContent = await submitFormWithoutReload(mainFormId);
		if (!saveContent)
			throw new Error(
				_ai_lang.error_saving_form.replace("%s", "template")
			);

		await sendTestEmail(email, campaignUid);

		checkSpamScoreFinalStep(email);
	} catch (error) {
		// Update the state
		$(scoreResultSelector).removeClass("invisible");
		return toggleLoadingState(stateSelector, error.message);
	}
};

/**
 * Check campaign spam score from confirm box
 *
 * @returns
 */
const checkSpamScoreConfirmBox = async () => {
	let stateSelector = "spam_score";
	const scoreResultSelector = "#" + stateSelector + " strong";

	// Get the test email. test email is set to container from backend
	let email = $("[data-customer-spam-test-email]").data(
		"customer-spam-test-email"
	);

	// Check if the state is already set
	if (aiContentState.spam_score) return;

	// Check if customer email is available
	if (!email.length) {
		errorToastr(_ai_lang.spam_test_email_not_found);
		return;
	}

	// Lock the state
	toggleLoadingState(stateSelector);

	// Save the template form and insert email in the test form

	try {
		let campaignUid = window.location.href
			.split("campaigns/")[1]
			.split("/")[0];

		if (!campaignUid.length)
			throw new Error(_ai_lang.campaign_id_not_found);

		// send email test request
		await sendTestEmail(email, campaignUid);
	} catch (error) {
		// Update the state
		$(scoreResultSelector).removeClass("invisible");
		return toggleLoadingState(stateSelector, error.message);
	}

	checkSpamScoreFinalStep(email);
};

let checkSpamScoreTimeout;
let spamTrialCount = 0;
//track success and reload after every 3 success as mail-tester allow only 3 per day for an email address.
let successfulSpamReportCounter = 0;

/**
 * Handle spam score checking and result. It also handle rechecking (pooling)
 *
 * @param {string} email The email address to which the test was sent to
 * @returns
 */
const checkSpamScoreFinalStep = async (email) => {
	let stateSelector = "spam_score";
	const scoreResultSelector = "#" + stateSelector + " strong";
	const updateSpamScore = (score) => {
		$(scoreResultSelector).text(score);
	};
	const isRandom = $("[data-is-random-email]").length;

	if (successfulSpamReportCounter >= 3 && isRandom)
		return window.location.reload();

	// Ensure state is set to loading
	if (!aiContentState.spam_score) toggleLoadingState(stateSelector);

	// Clear the timeout
	clearTimeout(checkSpamScoreTimeout);

	// Get the report from mailtester
	const resp = await customHttpRequest(
		window.AICONTENT_SPAM_SCORE_URL,
		"POST",
		{
			email,
		}
	);

	// Update the state
	toggleLoadingState(stateSelector);

	// If there is a message then show error toastr
	if (resp.message) return errorToastr(resp.message);

	// If waiting page is displayed then wait for some time and check again
	if (!resp?.score?.length) {
		// Stop after 15 trials
		if (spamTrialCount > 10) {
			$(viewReportModal + " iframe").attr(
				"srcdoc",
				`<p class='text-primary'>${_ai_lang.try_agian_later}</p>`
			);
			$(viewReportModal).modal("hide");
			updateSpamScore("0/0");
			errorToastr(_ai_lang.try_agian_later);
			return;
		}

		spamTrialCount++;
		updateSpamScore("wait...");

		// Set a timeout to check again after 14 seconds
		checkSpamScoreTimeout = setTimeout(() => {
			checkSpamScoreFinalStep(email);
		}, 15000);

		$(viewReportModal).modal("show");
	} else {
		spamTrialCount = 0;
		updateSpamScore(resp.score);
		successfulSpamReportCounter++;
	}
	console.log(resp.full_html.includes("micropay") && isRandom);
	if (resp.full_html.includes("micropay") && isRandom) {
		console.log(resp.url);
		//might have exhausted the 3 daily trial
		//window.open(resp.url, "_blank");
		$(viewReportModal + " iframe").removeAttr("srcdoc");
		$(viewReportModal + " iframe").attr("src", resp.url);
		return;
	}

	//update report view
	$(viewReportModal + " iframe").attr("srcdoc", resp.full_html);
};

/////////////////////////****General utils *////////////////////////

/**
 * Detects when the user stops typing inside an input element and calls a callback function.
 *
 * @param {HTMLElement} inputElement - The input element to detect typing in.
 * @param {function} callbackFunction - The function to call when the user stops typing.
 * @param {number} delay - The delay time in milliseconds to wait after the user stops typing.
 */
function detectStopTyping(inputElement, callbackFunction, delay = 3000) {
	let typingTimer; // Initialize a variable to hold the timer ID

	// Add an event listener to the input element for the 'input' event
	inputElement.addEventListener("input", function () {
		// Clear the existing timer if there is one
		clearTimeout(typingTimer);

		// Start a new timer with the specified delay and callback function
		typingTimer = setTimeout(callbackFunction, delay);
	});
	//clear the input when user loose focus
	inputElement.addEventListener("change", function () {
		clearTimeout(typingTimer);
	});
}

/**
 * Makes an HTTP request to the specified endpoint with the given method and data.
 *
 * @param {string} url The URL to make the request to.
 * @param {string} method The HTTP method to use.
 * @param {object} data The data to send in the request body.
 * @returns {Promise<object>} A Promise that resolves with the response data.
 */
async function customHttpRequest(url, method, data) {
	let requestBody = null;

	// If the request method is POST, include CSRF token in the request body
	if (method.toUpperCase() === "POST") {
		const csrfTokenName = "_token";
		const csrfTokenValue = CSRF_TOKEN;

		requestBody = new FormData();
		requestBody.append(csrfTokenName, csrfTokenValue);

		// Add the data to the request body
		Object.keys(data).forEach((key) => {
			const keyData = data[key];
			requestBody.append(
				key,
				typeof keyData === "object" ? JSON.stringify(keyData) : keyData
			);
		});
	}

	// Send the HTTP request using fetch()
	const response = await fetch(url, {
		method,
		body: requestBody,
	});

	let responseData;
	let responseText;

	try {
		// Parse the response body as JSON
		responseText = await response.text();
		responseData = JSON.parse(responseText);
	} catch (error) {
		// If the response is not JSON, extract the error message from the response HTML
		const errorMessage =
			responseText
				.match(/<title>(.*?)<\/title>/)[1]
				.trim()
				.substring(0, 300) ?? error.message;
		responseData = {
			message: errorMessage,
			success: false,
		};

		console.trace(error);
	}

	return responseData;
}

/**
 * Submits a form without reloading the page and displays a notification on success or error.
 *
 * @param {string} formId The ID of the form to submit.
 * @returns {Promise<boolean>} A Promise that resolves with true if the form submission was successful, and false otherwise.
 */
async function submitFormWithoutReload(formId) {
	const form = document.getElementById(formId); // Get the form by its ID
	const formData = new FormData(form); // Create a new FormData object from the form

	try {
		// Submit the form using fetch()
		const response = await fetch(form.action, {
			method: "POST",
			body: formData,
		});

		const responseHtml = await response.json();

		// Extract any success or error messages from the response HTML
		const isSuccess = responseHtml.status == "success";
		const errorNotes = responseHtml.message;

		// If there are no success or error messages, the server likely returned an error 500
		if (!isSuccess) {
			throw new Error(errorNotes);
		}

		return isSuccess;
	} catch (error) {
		console.log(error);
		errorToastr(error.message);
	}
	return false;
}

const chatGptIcon = `<svg style="width:18px;height:18px;fill:#007c89;" xmlns="http://www.w3.org/2000/svg" width="671.194" height="680.2487" viewBox="0 0 671.194 680.2487"><path d="M626.9464,278.4037a169.4492,169.4492,0,0,0-14.5642-139.187A171.3828,171.3828,0,0,0,427.7883,56.9841,169.45,169.45,0,0,0,299.9746.0034,171.3985,171.3985,0,0,0,136.4751,118.6719,169.5077,169.5077,0,0,0,23.1574,200.8775,171.41,171.41,0,0,0,44.2385,401.845,169.4564,169.4564,0,0,0,58.8021,541.0325a171.4,171.4,0,0,0,184.5945,82.2318A169.4474,169.4474,0,0,0,371.21,680.2454,171.4,171.4,0,0,0,534.7642,561.51a169.504,169.504,0,0,0,113.3175-82.2063,171.4116,171.4116,0,0,0-21.1353-200.9ZM371.2647,635.7758a127.1077,127.1077,0,0,1-81.6027-29.5024c1.0323-.5629,2.8435-1.556,4.0237-2.2788L429.13,525.7575a22.0226,22.0226,0,0,0,11.1306-19.27V315.5368l57.25,33.0567a2.0332,2.0332,0,0,1,1.1122,1.568V508.2972A127.64,127.64,0,0,1,371.2647,635.7758ZM97.3705,518.7985a127.0536,127.0536,0,0,1-15.2074-85.4256c1.0057.6037,2.7624,1.6768,4.0231,2.4012L221.63,514.01a22.04,22.04,0,0,0,22.2492,0L409.243,418.5281v66.1134a2.0529,2.0529,0,0,1-.818,1.7568l-136.92,79.0534a127.6145,127.6145,0,0,1-174.134-46.6532ZM61.7391,223.1114a127.0146,127.0146,0,0,1,66.3545-55.8944c0,1.1667-.067,3.2329-.067,4.6665V328.3561a22.0038,22.0038,0,0,0,11.1173,19.2578l165.3629,95.4695-57.2481,33.055a2.0549,2.0549,0,0,1-1.9319.1752l-136.933-79.1215A127.6139,127.6139,0,0,1,61.7391,223.1114ZM532.0959,332.5668,366.7308,237.0854l57.25-33.0431a2.0455,2.0455,0,0,1,1.93-.1735l136.934,79.0535a127.5047,127.5047,0,0,1-19.7,230.055V351.8247a21.9961,21.9961,0,0,0-11.0489-19.2579Zm56.9793-85.7589c-1.0051-.6174-2.7618-1.6769-4.0219-2.4L449.6072,166.1712a22.07,22.07,0,0,0-22.2475,0L261.9963,261.6543V195.5409a2.0529,2.0529,0,0,1,.818-1.7567l136.9205-78.988a127.4923,127.4923,0,0,1,189.34,132.0117ZM230.8716,364.6456,173.6082,331.589a2.0321,2.0321,0,0,1-1.1122-1.57V171.8835A127.4926,127.4926,0,0,1,381.5636,73.9884c-1.0322.5633-2.83,1.5558-4.0236,2.28L242.0957,154.5044a22.0025,22.0025,0,0,0-11.1306,19.2566Zm31.0975-67.0521L335.62,255.0559l73.6488,42.51v85.0481L335.62,425.1266l-73.6506-42.5122Z"/></svg>`;

function bindUI() {
	/** Common modal for getting input and displaying report */
	const modalHtmlTemplate = `
    <!-- modals -->
    <div class="modal modal-info fade" id="ai_content_prompt_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content prompt-modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">${_ai_lang.prompt}</h4>
                    <button type="button" class="close text-dark mt-0 mr-2" data-bs-dismiss="modal" aria-hidden="true">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="generate_form">
                        <div class="form-group">
                            <label for="context">${_ai_lang.prompt_label} <span class="text-danger">*</span></label>
                            <input data-bs-title="${_ai_lang.prompt_label}" data-container="body" data-bs-toggle="popover" data-bs-content="${_ai_lang.prompt_placeholder_hint}" class="form-control has-help-text" type="text" name="context" id="content_context" placeholder="${_ai_lang.prompt_placeholder}" required/>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary">${_ai_lang.generate}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-info fade fullscreen-modal" id="report_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <span class="button close text-dark" data-bs-dismiss="modal" aria-hidden="true">&times;</span>
                <div class="modal-body">
                    <iframe id="report-iframe"/>
                </div>
            </div>
        </div>
    </div>
`;

	$("body").append(modalHtmlTemplate);

	//load cached context into modal input
	aiCacheContext();

	$(generateFormSelector).submit(function (e) {
		e.preventDefault();
		generateContent();
		$(promptModalSelector).modal("hide");
		return false;
	});

	$(document).on("click", contentRegenerateSelector, function () {
		generateContent();
	});

	window.AICONTENT_CONTENT_GENERATOR_TYPE = "template";

	if ($(subjectInputSelector).length) {
		window.AICONTENT_CONTENT_GENERATOR_TYPE = "subject";
	}

	const refreshIcon = `
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 92 94.2"><defs><style>.cls-1{fill:none;stroke:#007c89;stroke-linecap:round;stroke-linejoin:round;stroke-width:7px;}.cls-2{fill:#007c89;}</style></defs>
            <g id="Layer_2" data-name="Layer 2">
            <g id="Layer_1-2" data-name="Layer 1">
            <path class="cls-1" d="M14.8,77.5A43.5,43.5,0,0,0,88.5,56.8"/>
            <polygon class="cls-2" points="4.1 89.6 3.5 56.8 32.2 72.6 4.1 89.6"/>
            <path class="cls-1" d="M3.5,37.4A43.6,43.6,0,0,1,46,3.5,43,43,0,0,1,77.2,16.7"/>
            <polygon class="cls-2" points="59.8 21.6 88.5 37.4 87.9 4.6 59.8 21.6"/>
            </g></g>
        </svg>`;

	const promptActionTemplate = `
    <div class="inline ml-4">
    <span class="anchor" data-bs-target="${promptModalSelector}" data-bs-toggle="modal">[${_ai_lang.generate} <span>${chatGptIcon}</span>]</span>
    <span data-deps="suggestions" class="anchor regenerate invisible" data-toggle="tooltip" data-original-title="${_ai_lang.regenerate}"> <span class="w-16">${refreshIcon}</span></span>
    </div>`;

	const spamScoreTemplate = `
    [<span class="anchor check_spam_score" data-bs-toggle="tooltip" data-original-title="${_ai_lang.check_spam_score_hint}">${_ai_lang.check_spam_score}</span>: <span id="spam_score"><strong data-deps="spam_score" class="anchor">0/0</strong></span>] 
    <span data-deps="spam_score" class="anchor text-primary invisible" data-bs-target="#report_modal" data-bs-toggle="modal"><span data-toggle="tooltip" data-original-title="${_ai_lang.view_full_report}"><span class="material-symbols-rounded">visibility</span></span></span>
`;

	/** subject line score */
	if (window.AICONTENT_CONTENT_GENERATOR_TYPE == "subject") {
		let parent = $(subjectInputSelector).parent("div");
		let label = parent.find("label span");
		let helpContainer = parent.find(".help");
		//insert subject report and suggestion template
		$(
			`<div class="ai_content_toolbar" id="ai_content_toolbar">
            <div class="">
                <div class="col-md-12" id="subject_score"></div>
                <div class="col-md-12 text-center">
                    <span class="anchor invisible" data-deps="subject_score" id="view_subject_score_report" data-bs-target="#report_modal" data-bs-toggle="modal">${_ai_lang.view_full_report}</span>
                </div>
            </div>

            <!-- suggestions -->
            <div id="suggestions" class="suggestions hidden">
                <h4 class="ml-4 text-danger">${_ai_lang.suggestion_list}:</h4>
                <ol></ol>
            </div>

    </div>`
		).insertAfter(helpContainer);

		if (window.ALLOW_CONTENT_GENERATION) {
			//insert toolbar after subject label
			$(promptActionTemplate).insertAfter(label);
			$(document).on("click", suggestionsSelector + " li a", function () {
				$(subjectInputSelector).val($(this).text()).trigger("change");
			});
		}

		checkSubjectScore();
		$(document).on("change", subjectInputSelector, function () {
			checkSubjectScore();
		});

		detectStopTyping(
			document.querySelector(subjectInputSelector),
			checkSubjectScore,
			5000
		);
	}

	/** Email content/template spam score checker */
	if (window.AICONTENT_CONTENT_GENERATOR_TYPE == "template") {
		$(
			"body:not(.leftbar) nav:not(.frontend):not(.navbar-backend) #mainAppNav .navbar-right ul.navbar-nav"
		).prepend(
			(window.ALLOW_CONTENT_GENERATION
				? `<li class="nav-item d-flex align-items-center  mr-4">${promptActionTemplate} <div id="suggestions"></div></li>`
				: "") +
				`<li class="nav-item d-flex align-items-center anchor">${spamScoreTemplate}</li>`
		);

		$(document).on("click", ".check_spam_score", function () {
			if (confirm(`${_ai_lang.confirm_spam_score}`)) {
				checkSpamScore();
			}
		});
	}

	//show spam report button on confirm page
	if ($(".confirm-campaign-box").length) {
		$(".confirm-campaign-box form .text-end").prepend(
			`<span class="anchor mr-4">${spamScoreTemplate}</span>`
		);
		$(document).on("click", ".check_spam_score", function () {
			checkSpamScoreConfirmBox();
		});
	}
}

/**
 * Pro builder integration
 */
function bindProBuilderUI() {
	const proBuilderAICache = {
		clear: () => {
			localStorage.removeItem("probuilder-suggestions");
			$(`#${suggestionsStateSelector} ol`).html("");
		},
		get: () => localStorage.getItem("probuilder-suggestions") ?? "",
		set: (content) =>
			localStorage.setItem("probuilder-suggestions", content),
	};
	const suggestionsStateSelector = "suggestions";

	const modalPromptContent = `
        <div class="prompt-modal-content h-75">
            <div class="modal-header">
                <h4 class="modal-title">${_ai_lang.prompt}</h4>
                <div>
                    <button type="button" class="close close-fancy p-0 bg-transparent border-0 text-dark mt-0 mr-2" data-bs-dismiss="modal" aria-hidden="true"><i class="fa fa-times fa-2x"></i></button>
                    <button id="prompt-expand" type="button" class="bg-transparent border-0 text-dark mt-0 mr-2"><i class="fa fa-expand fa-2x"></i></button>
                    <button id="prompt-minimize" type="button" class="bg-transparent border-0 text-dark mt-0 mr-2 d-none"><i class="fa fa-window-minimize fa-2x"></i></button>
                </div>
            </div>
            <div class="px-4">
                <form id="generate_form">
                    <div class="form-group">
                        <label for="context">${
							_ai_lang.block_prompt_label
						} <span class="text-danger">*</span></label>
                        <input data-bs-title="${
							_ai_lang.block_prompt_label
						}" data-container="body" data-bs-toggle="popover" data-bs-content="${
		_ai_lang.prompt_placeholder_hint
	}" class="form-control has-help-text" type="text" name="context" id="content_context" placeholder="${
		_ai_lang.block_prompt_placeholder
	}" required/>
                        <small>${_ai_lang.prompt_placeholder_hint}</small>
                    </div>
                    <div class="form-group">
                        <button class="btn btn-primary">${
							_ai_lang.generate
						}</button>
                    </div>
                </form>
            </div>
            <hr/>
            <!-- suggestions -->
            <div id="suggestions" class="suggestions position-relative overflow-auto h-75 ${
				proBuilderAICache.get()?.length ? "" : "hidden"
			}">
                <h4 class="ml-4 text-danger mb-0">${
					_ai_lang.suggestion_list
				}:</h4>
                <div  class="ml-4 mb-2"><small>${
					_ai_lang.suggestion_list_hint
				}</small></div>
                <ol>${proBuilderAICache.get()}</ol>
                <button class="position-absolute top-0 end-0 bg-transparent p-1 m-1 btn btn-danger float-right btn-xs text-danger" type="button" onclick="proBuilderAICache.clear();"><i class="fa fa-trash"></i></button>
            </div>
        </div>
    `;

	// Add the chatGPT icon to edit block toolbar
	const builderControl = ".builder-tool.builder-outline-selected-controls ul";
	if ($(builderControl)) {
		$(builderControl).prepend(
			`<li class="builder-action-selected-button bg-white" data-toggle="tooltip" title="${_ai_lang.generate}">
                <span onclick="window.parent.renderBuilderPrompt()" class="anchor">${chatGptIcon}</span>
            </li>`
		);
	}

	// Add the chagtGPT icon to general textarea
	const builderControl2 =
		".attributes[group-id='general'] textarea[class*='text-content text-editor']";
	if ($(builderControl2)) {
		$(
			`<span data-toggle="tooltip" title="${_ai_lang.generate}" onclick="window.parent.renderBuilderPrompt()" class="anchor">${chatGptIcon}</span>`
		).insertAfter(builderControl2);
	}

	window.proBuilderAICache = proBuilderAICache;
	window.notify = window.parent.notify;

	// Show prompt panel on pro builder right bar
	window.renderBuilderPrompt = () => {
		$("#builder_sidebar #nav-tabContent").toggle();
		$("#builder_sidebar .prompt-modal-content").toggle();
	};

	// Insert the HTML block into the active element
	window.insertSuggestionIntoBlock = ($selector) => {
		let suggestion = $($selector).html();
		let editorFrame = $("#builder_iframe").contents();
		let activeBlock = editorFrame.find(
			".builder-class-element-selected.mce-content-body"
		);
		activeBlock.html(suggestion);

		// Close prompt and show back tiny mce
		$("#builder_sidebar .prompt-modal-content .close").trigger("click");
		$(".content-left").trigger("click");
		activeBlock.trigger("click");
	};

	$("#builder_sidebar").append(modalPromptContent);
	$("#builder_sidebar .prompt-modal-content").hide();
	$("#builder_sidebar .prompt-modal-content .close").on(
		"click",
		window.renderBuilderPrompt
	);

	$(`#builder_sidebar ${generateFormSelector}`).submit(async function (e) {
		e.preventDefault();

		let context = $(contextInputSelector).val();

		// If suggestions are already present in the state, exit the function.
		if (aiContentState.suggestions || !context.length) {
			return;
		}

		// Lock state and show loading spinner.
		$("." + suggestionsStateSelector).removeClass("hidden");
		toggleLoadingState(suggestionsStateSelector);

		// Send HTTP POST request to AI content generator API.
		const resp = await customHttpRequest(
			window.AICONTENT_CONTENT_GENERATOR_URL + "/block",
			"POST",
			{
				context,
			}
		);

		// Unlock state and hide loading spinner.
		toggleLoadingState(suggestionsStateSelector);

		// If an error message is returned, show it in a toastr error message.
		if (resp.message) {
			return errorToastr(resp.message);
		}

		if (resp.suggestions?.length) {
			let html = "";
			for (let i = 0; i < resp.suggestions.length; i++) {
				const text = resp.suggestions[i];
				const id = Date.now() + "" + i;
				html += `<li><p class="suggestion_${id} anchor text-dark pb-4" onclick="insertSuggestionIntoBlock('.suggestion_${id}')">${text}</p></li>`;
			}

			// Update UI for list of selection.
			$(`#${suggestionsStateSelector} ol`).prepend(html).slideDown();
		}

		proBuilderAICache.set($(`#${suggestionsStateSelector} ol`).html());

		return false;
	});

	// maximize minimize
	$("#prompt-expand").on("click", function () {
		$(this).addClass("d-none");
		$(".prompt-modal-content").addClass("fullscreen");
		$("#prompt-minimize").removeClass("d-none");
	});
	$("#prompt-minimize").on("click", function () {
		$(this).addClass("d-none");
		$(".prompt-modal-content").removeClass("fullscreen");
		$("#prompt-expand").removeClass("d-none");
	});
}

//bind event on ready
document.addEventListener("DOMContentLoaded", function () {
	setTimeout(() => {
		bindUI();
		bindProBuilderUI();
	}, 1000);
});
