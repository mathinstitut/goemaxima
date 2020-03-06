$(document).ready(function () {
	/*
	 * Use the full page for show the PRT authoring interface
	 */
	/*Change width of PRT	part to use the full page space*/
	$('#il_prop_cont_question_prts label')
		.removeClass("col-sm-3 control-label")
		.addClass("col-sm-0 control-label");
	$('#il_prop_cont_question_prts label').next()
		.removeClass("col-sm-9")
		.addClass("col-sm-12");
	/*Change title to be like the question header*/
	var prt_text = $("label[for='question_prts']").html();
	$("label[for='question_prts']").html("<h3 class='ilHeader'>" + prt_text + "</h3>")

	/*
	 * Use the full page for show the Inputs authoring interface
	 */
	/*Change width of Input	part to use the full page space*/
	$('#il_prop_cont_question_inputs label')
		.removeClass("col-sm-3 control-label")
		.addClass("col-sm-0 control-label");
	$('#il_prop_cont_question_inputs label').next()
		.removeClass("col-sm-9")
		.addClass("col-sm-12");
	/*Change title to be like the question header*/
	var input_text = $("label[for='question_inputs']").html();
	$("label[for='question_inputs']").html("<h3 class='ilHeader'>" + input_text + "</h3>")

	/*
	 * Use the full page for show the options authoring interface
	 */
	/*Change width of Input	part to use the full page space*/
	$('#il_prop_cont_question_options label')
		.removeClass("col-sm-3 control-label")
		.addClass("col-sm-0 control-label");
	$('#il_prop_cont_question_options label').next()
		.removeClass("col-sm-9")
		.addClass("col-sm-12");
	/*Change title to be like the question header*/
	var options_text = $("label[for='question_options']").html();
	$("label[for='question_options']").html("<h3 class='ilHeader'>" + options_text + "</h3>")

});