/**
 * Copyright (c) 2013, Lukas Reschke <lukas@statuscode.ch>
 *               2013, Morris Jobke <morris.jobke@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

$(function() {
	$('#pass2').keyup(function() {
		var password = $(this).val();

		// hide strength meter if no input is provided
		$('#password-strength-container').css(
			'display',
			(password === '') ? 'none': 'inline-block'
		);

		var result = zxcvbn(password);
		var titles = [
			t('core', 'Weakest'),
			t('core', 'Weak'),
			t('core', 'So-so'),
			t('core', 'Good'),
			t('core', 'Perfect')
		];

		switch(result.score) {
			case 0:
			case 1:
				var css = 'password-bad';
				break;
			case 2:
				var css = 'password-medium';
				break;
			case 3:
			case 4:
				var css = 'password-good';
				break;
		}

		$('#password-strength').attr('class', css);
		// possible scores: 0-4
		$('#password-strength').css('width', (result.score * 25) + '%' );
		$('#password-strength-container').attr('title', titles[result.score]);
	});
});