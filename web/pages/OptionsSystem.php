<?php
// ----------------------------------
//  __/\\\\____________/\\\\___________________/\\\\\\\\\\\____/\\\\\\\\\\\\\___
//   _\/\\\\\\________/\\\\\\_________________/\\\/////////\\\_\/\\\/////////\\\_
//	_\/\\\//\\\____/\\\//\\\____/\\\__/\\\__\//\\\______\///__\/\\\_______\/\\\_
//	 _\/\\\\///\\\/\\\/_\/\\\___\//\\\/\\\____\////\\\_________\/\\\\\\\\\\\\\\__
//	  _\/\\\__\///\\\/___\/\\\____\//\\\\\________\////\\\______\/\\\/////////\\\_
//	   _\/\\\____\///_____\/\\\_____\//\\\____________\////\\\___\/\\\_______\/\\\_
//		_\/\\\_____________\/\\\__/\\_/\\\______/\\\______\//\\\__\/\\\_______\/\\\_
//		 _\/\\\_____________\/\\\_\//\\\\/______\///\\\\\\\\\\\/___\/\\\\\\\\\\\\\/__
//		  _\///______________\///___\////__________\///////////_____\/////////////_____
//			By toulousain79 ---> https://github.com/toulousain79/
//
//#####################################################################
//
//	Copyright (c) 2013 toulousain79 (https://github.com/toulousain79/)
//	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
//	The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
//	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
//	IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
//	--> Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
//
//#################### FIRST LINE #####################################

global $MySB_DB, $CurrentUser;
require_once(WEB_INC . '/languages/' . $_SESSION['Language'] . '/' . basename(__FILE__));

$PeerguardianIsInstalled = $MySB_DB->get("services", "is_installed", ["serv_name" => "PeerGuardian"]);
$OpenVPNIsInstalled = $MySB_DB->get("services", "is_installed", ["serv_name" => "OpenVPN"]);
$DNScryptIsInstalled = $MySB_DB->get("services", "is_installed", ["serv_name" => "DNScrypt-proxy"]);
$LogwatchIsInstalled = $MySB_DB->get("services", "is_installed", ["serv_name" => "LogWatch"]);
$IsMainUser = (MainUser($CurrentUser)) ? true : false;
$system_datas = $MySB_DB->get("system", ["dnscrypt", "logwatch", "pgl_email_stats", "pgl_watchdog_email", "ip_restriction", "rt_active"], ["id_system" => 1]);

// Get values from database
$DNScrypt_db = $system_datas['dnscrypt'];
$LogWatch_db = $system_datas['logwatch'];
$PGL_EmailStats_db = $system_datas['pgl_email_stats'];
$PGL_WatchdogEmail_db = $system_datas['pgl_watchdog_email'];
$IP_Restriction_db = $system_datas['ip_restriction'];
$Renting_db = $system_datas['rt_active'];
$OpenVPN_Proto_db =  $MySB_DB->get("services", "port_udp1", ["serv_name" => "OpenVPN"]);
switch ($OpenVPN_Proto_db) {
	case '':
		$OpenVPN_Proto_db = 'TCP';
		$openvpn_port1 =  $MySB_DB->get("services", "port_tcp1", ["serv_name" => "OpenVPN"]);
		$openvpn_port2 =  $MySB_DB->get("services", "port_tcp2", ["serv_name" => "OpenVPN"]);
		$openvpn_port3 =  $MySB_DB->get("services", "port_tcp3", ["serv_name" => "OpenVPN"]);
		break;
	default:
		$OpenVPN_Proto_db = 'UDP';
		$openvpn_port1 =  $MySB_DB->get("services", "port_udp1", ["serv_name" => "OpenVPN"]);
		$openvpn_port2 =  $MySB_DB->get("services", "port_udp2", ["serv_name" => "OpenVPN"]);
		$openvpn_port3 =  $MySB_DB->get("services", "port_udp3", ["serv_name" => "OpenVPN"]);
		break;
}

if (isset($_POST['submit'])) {
	$PGL_EmailStats_post = $_POST['PGL_EmailStats'];
	$PGL_EmailWD_post = $_POST['PGL_EmailWD'];
	$IP_restriction_post = $_POST['IP_restriction_post'];
	$OpenVPN_Proto_post = $_POST['OpenVPN_Proto_post'];
	$DNScrypt_post = $_POST['DNScrypt_post'];
	$LogWatch_post = $_POST['LogWatch_post'];
	$Renting_post = $_POST['Renting_post'];
	$type = 'success';
	$NoChange = true;

	// 1 - First, we apply new paramaters WITHOUT needed of create again MySB Security rules
	if ($OpenVPN_Proto_db != $OpenVPN_Proto_post) {
		switch ($OpenVPN_Proto_post) {
			case 'TCP':
				$result = $MySB_DB->update("services", 	["port_tcp1" => $openvpn_port1, "port_tcp2" => $openvpn_port2, "port_tcp3" => $openvpn_port3, "port_udp1" => "", "port_udp2" => "", "port_udp3" => ""], ["serv_name" => "OpenVPN"]);
				break;
			default:
				$result = $MySB_DB->update("services", 	["port_tcp1" => "", "port_tcp2" => "", "port_tcp3" => "", "port_udp1" => $openvpn_port1, "port_udp2" => $openvpn_port2, "port_udp3" => $openvpn_port3], ["serv_name" => "OpenVPN"]);
				break;
		}

		// OpenVPN: 0 disabled / 1 enabled / -1 no changes
		if ( $result >= 0 ) {
			$args = "OpenVPN:1";
			$NoChange = false;
		} else {
			$args = "OpenVPN:0";
		}
	} else {
		$args = "OpenVPN:-1";
	}

	// 2 - Next, we apply new paramaters WITH (maybe) needed of create again MySB Security rules
	if ( ($Renting_db != $Renting_post) || ($IP_Restriction_db != $IP_restriction_post) || ($PGL_EmailStats_db != $PGL_EmailStats_post) || ($PGL_WatchdogEmail_db != $PGL_EmailWD_post) || ($DNScrypt_db != $DNScrypt_post) || ($LogWatch_db != $LogWatch_post) ) {
		$result = $MySB_DB->update("system", ["rt_active" => "$Renting_post", "ip_restriction" => "$IP_restriction_post", "pgl_email_stats" => "$PGL_EmailStats_post", "pgl_watchdog_email" => "$PGL_EmailWD_post", "dnscrypt" => "$DNScrypt_post", "logwatch" => "$LogWatch_post"], ["id_system" => 1]);

		if( $result >= 0 ) {
			$NoChange = false;

			// LogWatch: 0 disabled / 1 enabled / -1 no changes
			if ( $LogWatch_db != $LogWatch_post ) {
				$args .= "$args|LogWatch:$LogWatch_post";
			} else {
				$args = "$args|LogWatch:-1";
			}
			// DNScrypt: 0 disabled / 1 enabled / -1 no changes
			if ( $DNScrypt_db != $DNScrypt_post ) {
				$args = "$args|DNScrypt:$DNScrypt_post";
			} else {
				$args = "$args|DNScrypt:-1";
			}
			if ( ($IP_Restriction_db != $IP_restriction_post) || ($PGL_EmailStats_db != $PGL_EmailStats_post) || ($PGL_WatchdogEmail_db != $PGL_EmailWD_post) ) {
				$args = "$args|MySB_SecurityRules";
			}
		}
	} else {
		$args = "$args|LogWatch:-1|DNScrypt:-1";
	}

	// 3 - Next, we apply new paramaters WITHOUT needed of create again MySB Security rules
	if ( $Renting_db != $Renting_post ) {
		$result = $MySB_DB->update("system", ["rt_active" => "$Renting_post"], ["id_system" => 1]);
		if( $result >= 0 ) {
			$NoChange = false;
		}
	}

	// Get new values from database
	$PGL_EmailStats_db = $PGL_EmailStats_post;
	$PGL_WatchdogEmail_db = $PGL_EmailWD_post;
	$IP_Restriction_db = $IP_restriction_post;
	$OpenVPN_Proto_db = $OpenVPN_Proto_post;
	$LogWatch_db = $LogWatch_post;
	$Renting_db = $Renting_post;

	if ($NoChange) {
		GenerateMessage('message_only', 'information', Global_NoChange);
	} else {
		GenerateMessage('Options_System', $type, $message, $args);
	}
}
?>

<form class="form_settings" method="post" action="">
<div align="center" style="margin-top: 10px; margin-bottom: 20px;">
	<?php if ($PeerguardianIsInstalled == '1') { ?>
	<fieldset>
	<legend><?php echo MainUser_OptionsSystem_Title_PGL; ?></legend>
	<table>
		<tr>
			<td><?php echo MainUser_OptionsSystem_PGL_Stats; ?></td>
			<td>
				<?php switch ($PGL_EmailStats_db) {
					case '1':
						$class = 'greenText';
						$options = '<option selected="selected" value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option value="0" class="redText">' .Global_No. '</option>';
						break;
					default:
						$class = 'redText';
						$options = '<option value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option selected="selected" value="0" class="redText">' .Global_No. '</option>';
						break;
				} ?>
				<select name="PGL_EmailStats" style="width:80px; height: 28px;" class="<?php echo $class; ?>" onchange="this.className=this.options[this.selectedIndex].className"><?php echo $options; ?></select>
			</td>
			<td><?php echo MainUser_OptionsSystem_PGL_Whathdog; ?></td>
			<td>
				<?php switch ($PGL_WatchdogEmail_db) {
					case '1':
						$class = 'greenText';
						$options = '<option selected="selected" value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option value="0" class="redText">' .Global_No. '</option>';
						break;
					default:
						$class = 'redText';
						$options = '<option value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option selected="selected" value="0" class="redText">' .Global_No. '</option>';
						break;
				} ?>
				<select name="PGL_EmailWD" style="width:80px; height: 28px;" class="<?php echo $class; ?>" onchange="this.className=this.options[this.selectedIndex].className"><?php echo $options; ?></select>
			</td>
		</tr>
	</table>
	</fieldset>
	<?php } ?>

	<fieldset>
	<legend><?php echo MainUser_OptionsSystem_Title_Iptables; ?></legend>
	<table>
		<tr>
			<td><?php echo MainUser_OptionsSystem_Iptables_Restrict; ?></td>
			<td>
				<?php switch ($IP_Restriction_db) {
					case '1':
						$class = 'greenText';
						$options = '<option selected="selected" value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option value="0" class="redText">' .Global_No. '</option>';
						break;
					default:
						$class = 'redText';
						$options = '<option value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option selected="selected" value="0" class="redText">' .Global_No. '</option>';
						break;
				} ?>
				<select name="IP_restriction_post" style="width:80px; height: 28px;" class="<?php echo $class; ?>" onchange="this.className=this.options[this.selectedIndex].className"><?php echo $options; ?></select>
			</td>
		</tr>
	</table>
	</fieldset>

	<fieldset>
	<legend><?php echo MainUser_OptionsSystem_Title_Renting; ?></legend>
	<table>
		<tr>
			<td><?php echo MainUser_OptionsSystem_Activate; ?></td>
			<td>
				<?php switch ($Renting_db) {
					case '1':
						$class = 'greenText';
						$options = '<option selected="selected" value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option value="0" class="redText">' .Global_No. '</option>';
						break;
					default:
						$class = 'redText';
						$options = '<option value="1" class="greenText">' .Global_Yes. '</option>';
						$options .= '<option selected="selected" value="0" class="redText">' .Global_No. '</option>';
						break;
				} ?>
				<select name="Renting_post" style="width:80px; height: 28px;" class="<?php echo $class; ?>" onchange="this.className=this.options[this.selectedIndex].className"><?php echo $options; ?></select>
			</td>
		</tr>
	</table>
	</fieldset>

	<br />
	<?php if ($OpenVPNIsInstalled == '1') { ?>
	<fieldset>
	<legend><?php echo MainUser_OptionsSystem_Title_OpenVPN; ?></legend>
	<table>
		<tr>
			<td><?php echo MainUser_OptionsSystem_OpenVPN_Proto; ?></td>
			<td>
				<select name="OpenVPN_Proto_post" style="width:80px; height: 28px;">
				<?php switch ($OpenVPN_Proto_db) {
					case 'UDP':
						echo '<option selected="selected" value="UDP">UDP</option>';
						echo '<option value="TCP">TCP</option>';
						break;
					default:
						echo '<option value="UDP">UDP</option>';
						echo '<option selected="selected" value="TCP">TCP</option>';
						break;
				} ?>
				</select>
			</td>
		</tr>
	</table>
	</fieldset>
	<?php } ?>

	<?php if ($DNScryptIsInstalled == '1') { ?>
	<fieldset>
	<legend><?php echo MainUser_OptionsSystem_Title_DNScrypt; ?></legend>
	<table>
		<tr>
			<td><?php echo MainUser_OptionsSystem_Activate; ?></td>
			<td>
				<?php switch ($DNScrypt_db) {
					case '1':
						$class = 'greenText';
						$options = '<option selected="selected" value="1" class="greenText">' . Global_Yes . '</option>';
						$options .= '<option value="0" class="redText">' . Global_No . '</option>';
						break;
					default:
						$class = 'redText';
						$options = '<option value="1" class="greenText">' . Global_Yes . '</option>';
						$options .= '<option selected="selected" value="0" class="redText">' . Global_No . '</option>';
						break;
				} ?>
				<select name="DNScrypt_post" style="width:80px; height: 28px;" class="<?php echo $class; ?>" onchange="this.className=this.options[this.selectedIndex].className"><?php echo $options; ?></select>
			</td>
		</tr>
	</table>
	</fieldset>
	<?php } ?>

	<?php if ($LogwatchIsInstalled == '1') { ?>
	<fieldset>
	<legend><?php echo MainUser_OptionsSystem_Title_Logwatch; ?></legend>
	<table>
		<tr>
			<td><?php echo MainUser_OptionsSystem_Activate; ?></td>
			<td>
				<?php switch ($LogWatch_db) {
					case '1':
						$class = 'greenText';
						$options = '<option selected="selected" value="1" class="greenText">' . Global_Yes . '</option>';
						$options .= '<option value="0" class="redText">' . Global_No . '</option>';
						break;
					default:
						$class = 'redText';
						$options = '<option value="1" class="greenText">' . Global_Yes . '</option>';
						$options .= '<option selected="selected" value="0" class="redText">' . Global_No . '</option>';
						break;
				} ?>
				<select name="LogWatch_post" style="width:80px; height: 28px;" class="<?php echo $class; ?>" onchange="this.className=this.options[this.selectedIndex].className"><?php echo $options; ?></select>
			</td>
		</tr>
	</table>
	</fieldset>
	<?php } ?>

	<input class="submit" style="width:<?php echo strlen(Global_SaveChanges)*10; ?>px; margin-top: 10px;" name="submit" type="submit" value="<?php echo Global_SaveChanges; ?>" />

	</div>
</form>

<?php
//#################### LAST LINE ######################################
