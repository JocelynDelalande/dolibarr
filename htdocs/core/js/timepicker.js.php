<?php
/* Copyright (C) 2012	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/core/js/timepicker.js.php
 * \brief      File that include javascript functions for timepicker
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

require_once '../../main.inc.php';

// Define javascript type
header('Content-type: text/javascript; charset=UTF-8');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');
?>

// For JQuery Timepicker
jQuery(function($){
	$.timepicker.regional['<?php echo $langs->defaultlang ?>'] = {
		timeOnlyTitle: '<?php echo $langs->trans("TimeOnly") ?>',
		timeText: '<?php echo $langs->trans("Time") ?>',
		hourText: '<?php echo $langs->trans("Hour") ?>',
		minuteText: '<?php echo $langs->trans("Minute") ?>',
		secondText: '<?php echo $langs->trans("Second") ?>',
		millisecText: '<?php echo $langs->trans("Millisecond") ?>',
		timezoneText: '<?php echo $langs->trans("Timezone") ?>',
		currentText: '<?php echo $langs->trans("Now") ?>',
		closeText: '<?php echo $langs->trans("Close2") ?>',
		timeFormat: 'HH:mm',
		amNames: ['AM', 'A'],
		pmNames: ['PM', 'P'],
		isRTL: <?php echo ($langs->trans("DIRECTION")=='rtl'?'true':'false'); ?>
	};
	$.timepicker.setDefaults($.timepicker.regional['<?php echo $langs->defaultlang ?>']);
});

<?php
if (is_object($db)) $db->close();
?>
