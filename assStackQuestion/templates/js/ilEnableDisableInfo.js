/* Copyright (c) 1998-2017 ILIAS open source, Extended GPL, see docs/LICENSE */

il.EnableDisableInfo = new function () {

	/**
	 * Self reference for usage in event handlers
	 * @type object
	 * @private
	 */
	var self = this;

	/**
	 * Elements of the current page that must be hidden/shown
	 * @type {NodeList}
	 */
	var divsToHide = document.getElementsByClassName("help-block");

	/**
	 * Configuration class for info messages
	 * @type {{show: number, ajax_url: string}}
	 */
	var info_config = {
		show: 1,// show info messages or not
		ajax_url: ''// ajax_url
	};

	/**
	 * Initialize method called from assStackQuestionGUI::editQuestionForm()
	 * @param a_info_config start configuration as JSON
	 */
	this.initInfoMessages = function (a_info_config) {

		info_config = a_info_config;

		//Show or hide messages depending on the current status of the show value in session.
		if (info_config.show) {
			self.showInfoMessages();
		} else {
			self.hideInfoMessages();
		}

		//If click on toolbar button, change status
		$("#enable_disable_info").click(self.toggleInfo);

		//If click on show guide
		$("#auth_guide_name").click(self.showGuide);
	};


	/**
	 * Changes current status of the showing of info messages
	 * @returns {boolean}
	 */
	this.toggleInfo = function () {

		//Toggler
		if (info_config.show == 0) {
			info_config.show = 1;
		} else {
			info_config.show = 0;
		}

		if (info_config.show) {
			self.showInfoMessages();
		} else {
			self.hideInfoMessages();
		}

		self.sendInfoState();
		return false;
	};

	/**
	 * Set visibility of info messages to block
	 */
	this.showInfoMessages = function () {

		for (var i = 0; i < divsToHide.length; i++) {
			divsToHide[i].style.display = 'block';
		}
		info_config.show = 1;
	};

	/**
	 * Set visibility of info messages to none
	 */
	this.hideInfoMessages = function () {
		for (var i = 0; i < divsToHide.length; i++) {
			divsToHide[i].style.display = 'none';
		}
		document.getElementsByClassName("help-block");
		info_config.show = 0;
	};

	/**
	 * Send new info messages state per Ajax
	 */
	this.sendInfoState = function () {
		$.get(info_config.ajax_url, {
			'show': info_config.show
		}).done(function (data) {
			//alert(info_config.ajax_url);
		});
	}

	/**
	 * open a new tab with the auth guide
	 * @returns {boolean}
	 */
	this.showGuide = function () {
		window.open("https://github.com/maths/moodle-qtype_stack/blob/master/doc/en/index.md");
	}
}