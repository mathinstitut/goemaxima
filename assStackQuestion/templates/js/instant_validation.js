/**
 * Character selector object
 * (anonymous constructor function)
 */
il.instant_validation = new function () {

	/**
	 * Self reference for usage in event handlers
	 * @type object
	 * @private
	 */
	var self = this;


	/**
	 * Configuration
	 * Has to be provided as JSON when init() is called
	 * @type object
	 * @private
	 */
	var config = {
	};

	/**
	 * Texts to be dynamically rendered
	 * @type object
	 * @private
	 */
	var texts = {
		page: ''
	};


	//setup before functions
	var typingTimer;                //timer identifier
	var doneTypingInterval = 2000;  //time in ms, 5 second for example


	/**
	 * Initialize the selector
	 * called from ilTemplate::addOnLoadCode,
	 * @param object    start configuration as JSON
	 * @param object    texts to be dynamically rendered
	 */
	this.init = function (a_config, a_texts) {
		config = a_config;
		texts = a_texts;

		$('tr#xqcas_question_display input[type="text"]').keyup(function (event) {
			delay(function () {
				var name = event.target.name;
				name = name.replace(/xqcas_/, '', name);
				var i = name.indexOf('_');
				var question_id = name.substr(0, i);
				var input_name = name.substr(i + 1);
				if (input_name.indexOf("_sub_") > -1) {
					var matrix_input_name = input_name.substr(0, input_name.indexOf("_sub_"));
					var rows = $('#xqcas_input_matrix_height_' + matrix_input_name).html();
					var columns = $('#xqcas_input_matrix_width_' + matrix_input_name).html();
					var user_response = 'matrix(';
					for (var r = 0; r < rows; r++) {
						user_response += '[';
						for (var c = 0; c < columns; c++) {
							var value = $('#xqcas_' + question_id + '_' + matrix_input_name + '_sub_' + r + '_' + c).val();
							if(value.length == 0){
								user_response += '?';
							}else{
								user_response += value;
							}
							if(c < columns-1){
								user_response += ',';
							}
						}
						user_response += ']';
						if(r < rows-1){
							user_response += ',';
						}
					}
					user_response += ')';
					var input_name = matrix_input_name;
					var input_value = user_response;
				} else {
					var input_value = $('#xqcas_' + question_id + '_' + input_name).val();
				}

				$.get(config.validate_url, {
					'question_id': question_id,
					'input_name': input_name,
					'input_value': input_value
				})

					.done(function (data) {
						$('#validation_xqcas_' + question_id + '_' + input_name).html(data);
						MathJax.Hub.Queue(["Typeset", MathJax.Hub, 'validation_xqcas_' + question_id + '_' + input_name]);
						$('#validation_xqcas_roll_' + question_id + '_' + input_name).html("");
					});


				/**
				 * Hide current question feedback
				 */
				$(".alert").hide();
				$(".test_specific_feedback").hide();
				/*$(".ilAssQuestionRelatedNavigationContainer:first").nextUntil(".ilAssQuestionRelatedNavigationContainer").hide();*/

				var img = new Image();
				img.src = "Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/css/ajax-loader.gif";
				$('#validation_xqcas_roll_' + question_id + '_' + input_name).html(img)

				return false;
			}, doneTypingInterval);
		});

		$('tr#xqcas_question_display textarea[rows="5"]').keyup(function (event) {
			delay(function () {

				var name = event.target.name;
				name = name.replace(/xqcas_/, '', name);
				var i = name.indexOf('_');
				var question_id = name.substr(0, i);
				var input_name = name.substr(i + 1);
				var input_value = $('#xqcas_' + question_id + '_' + input_name).val();

				$.get(config.validate_url, {
					'question_id': question_id,
					'input_name': input_name,
					'input_value': input_value
				})

					.done(function (data) {
						$('#validation_xqcas_' + question_id + '_' + input_name).html(data);
						MathJax.Hub.Queue(["Typeset", MathJax.Hub, 'validation_xqcas_' + question_id + '_' + input_name]);
						$('#validation_xqcas_roll_' + question_id + '_' + input_name).html("");
					});

				var img = new Image();
				img.src = "Customizing/global/plugins/Modules/TestQuestionPool/Questions/assStackQuestion/templates/css/ajax-loader.gif";
				$('#validation_xqcas_roll_' + question_id + '_' + input_name).html(img)

				return false;
			}, doneTypingInterval);
		});

	};

	var delay = (function () {
		var timer = 0;

		return function (callback, ms) {
			clearTimeout(timer);
			timer = setTimeout(callback, ms);
		};
	})();

	var matrix_value = (function () {
		var rows;
		var columns;

		return 5;
		for (rows = 0; rows < 0; rows++) {
			for (columns = 0; columns < 0; rows++) {

			}
		}
	})();
};