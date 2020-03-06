/**
 * Character selector object
 * (anonymous constructor function)
 */
il.assStackQuestion = new function () {

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
	var config = {};

	/**
	 * Texts to be dynamically rendered
	 * @type object
	 * @private
	 */
	var texts = {
		page: ''
	};


	/**
	 * Initialize the selector
	 * called from ilTemplate::addOnLoadCode,
	 * @param object    start configuration as JSON
	 * @param object    texts to be dynamically rendered
	 */
	this.init = function (a_config, a_texts) {
		config = a_config;
		texts = a_texts;
		$('tr#xqcas_question_display button, tr#xqcas_question_display span.input-group-addon,tr#xqcas_question_display span.glyphicon.glyphicon-ok').click(self.validate);
	};


	/**
	 * Send the current panel state per ajax
	 */
	this.validate = function (event) {
		var name = "";
		if (event.target.name === undefined) {
			name = event.target.getAttribute('name');
			if (name === null) {
				alert(5);
			}
		} else {
			name = event.target.name;
		}
		name = name.replace(/cmd\[xqcas_/, '', name);
		name = name.replace(/\]/, '', name);
		var i = name.indexOf('_');
		var question_id = name.substr(0, i);
		var input_name = name.substr(i + 1);
		var is_matrix = $('#xqcas_' + question_id + '_' + input_name + '_sub_0_0').html();

		if (typeof is_matrix === "string") {
			var rows = $('#xqcas_input_matrix_height_' + input_name).html();
			var columns = $('#xqcas_input_matrix_width_' + input_name).html();
			var user_response = 'matrix(';
			for (var r = 0; r < rows; r++) {
				user_response += '[';
				for (var c = 0; c < columns; c++) {
					var value = $('#xqcas_' + question_id + '_' + input_name + '_sub_' + r + '_' + c).val();
					if (value.length == 0) {
						user_response += '?';
					} else {
						user_response += value;
					}
					if (c < columns - 1) {
						user_response += ',';
					}
				}
				user_response += ']';
				if (r < rows - 1) {
					user_response += ',';
				}
			}
			user_response += ')';
			var input_value = user_response;
		} else {
			var input_value = $('#xqcas_' + question_id + '_' + input_name).val();
		}

		/**
		 * Hide current question feedback
		 */
		$(".alert").hide();
		$(".test_specific_feedback").hide();
		/*
		$(".ilAssQuestionRelatedNavigationContainer:first").nextUntil(".ilAssQuestionRelatedNavigationContainer").hide();*/

		$.get(config.validate_url, {
			'question_id': question_id,
			'input_name': input_name,
			'input_value': input_value
		})
			.done(function (data) {
				$('#validation_xqcas_' + question_id + '_' + input_name).html(data);
				MathJax.Hub.Queue(["Typeset", MathJax.Hub, 'validation_xqcas_' + question_id + '_' + input_name]);
			});

		return false;
	}
};