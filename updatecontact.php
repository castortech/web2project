<?php /* CONTACTS $Id: updatecontact.php,v 1.00 */
require_once 'base.php';
require_once W2P_BASE_DIR . '/includes/config.php';

if (!isset($GLOBALS['OS_WIN'])) {
	$GLOBALS['OS_WIN'] = (stristr(PHP_OS, "WIN") !== false);
}

// tweak for pathname consistence on windows machines
require_once W2P_BASE_DIR . '/includes/main_functions.php';
require_once W2P_BASE_DIR . '/includes/db_adodb.php';
require_once W2P_BASE_DIR . '/includes/db_connect.php';
require_once W2P_BASE_DIR . '/classes/query.class.php';
require_once W2P_BASE_DIR . '/classes/ui.class.php';
$AppUI = new CAppUI();
require_once W2P_BASE_DIR . '/classes/date.class.php';
require_once W2P_BASE_DIR . '/modules/contacts/contacts.class.php';

$updatekey = w2PgetParam($_GET, 'updatekey', 0);
$q = new DBQuery;
$q->addTable('contacts');
$q->addQuery("contact_id");
$q->addWhere('contact_updatekey="' . $updatekey . '"');
$contactkey = $q->loadList();
$q->clear();

$contact_id = count($contactkey) ? $contactkey[0]['contact_id'] : 0;
$company_id = intval(w2PgetParam($_REQUEST, 'company_id', 0));
$company_name = w2PgetParam($_REQUEST, 'company_name', null);

// check permissions for this record

if (!$contact_id) {
	echo ($AppUI->_('You are not authorized to use this page. If you should be authorized please contact') . ' ' . $w2Pconfig['company_name'] . ' ' . $AppUI->_('to give you another valid link, thank you.'));
	exit;
}

// load the record data
$msg = '';
$row = new CContact();

if (!$row->load($contact_id) && $contact_id > 0) {
	$AppUI->setMsg('Contact');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect();
} else
	if ($row->contact_private && $row->contact_owner != $AppUI->user_id && $row->contact_owner && $contact_id != 0) {
		// check only owner can edit
		$AppUI->redirect('m=public&a=access_denied');
	}

$df = $AppUI->getPref('SHDATEFORMAT');
$df .= " " . $AppUI->getPref('TIMEFORMAT');

// setup the title block
$ttl = $contact_id > 0 ? 'Edit Contact' : 'Add Contact';
$company_detail = $row->getCompanyDetails();
$dept_detail = $row->getDepartmentDetails();
if ($contact_id == 0 && $company_id > 0) {
	$company_detail['company_id'] = $company_id;
	$company_detail['company_name'] = $company_name;
	echo $company_name;
}

$uistyle = 'web2project';
$outsider = $row->contact_first_name . ' ' . $row->contact_last_name;
require W2P_BASE_DIR . '/style/' . $uistyle . '/overrides.php';
require W2P_BASE_DIR . '/style/' . $uistyle . '/header.php';

if (function_exists('styleRenderBoxTop')) {
	echo styleRenderBoxTop();
}
?>

<script language="javascript">
<?php
echo "window.company_id=" . w2PgetParam($company_detail, 'company_id', 0) . ";\n";
echo "window.company_value='" . addslashes(w2PgetParam($company_detail, 'company_name', "")) . "';\n";
?>

function submitIt() {
	var form = document.changecontact;
	if (form.contact_last_name.value.length < 1) {
		alert( "<?php echo $AppUI->_('contactsValidName', UI_OUTPUT_JS); ?>" );
		form.contact_last_name.focus();
	} else if (form.contact_order_by.value.length < 1) {
		alert( "<?php echo $AppUI->_('contactsOrderBy', UI_OUTPUT_JS); ?>" );
		form.contact_order_by.focus();
	} else {
		form.submit();
	}
}

function popDepartment() {
//        window.open('./index.php?m=public&a=selector&dialog=1&callback=setDepartment&table=departments&hide_company=1&company_id=' + window.company_id, 'department','left=50,top=50,height=250,width=400,resizable');
	window.open("./index.php?m=contacts&a=select_contact_company&dialog=1&table_name=departments&company_id="+window.company_id, "company", "left=50,top=50,height=250,width=400,resizable");
}

function setDepartment( key, val ){
	var f = document.changecontact;
 	if (val != '') {
    	f.contact_department.value = key;
			f.contact_department_name.value = val;
    }
}

function popCompany() {
//        window.open('./index.php?m=public&a=selector&dialog=1&callback=setCompany&table=companies', 'company','left=50,top=50,height=250,width=400,resizable');
	window.open("./index.php?m=contacts&a=select_contact_company&dialog=1&table_name=companies&company_id=<?php echo $company_detail['company_id']; ?>", "company", "left=50,top=50,height=250,width=400,resizable");
}

function setCompany( key, val ){
	var f = document.changecontact;
 	if (val != '') {
    	f.contact_company.value = key;
			f.contact_company_name.value = val;
    	if ( window.company_id != key ){
    		f.contact_department.value = "";
				f.contact_department_name.value = "";
    	}
    	window.company_id = key;
    	window.company_value = val;
    }
}

function delIt(){
	var form = document.changecontact;
	if(confirm( "<?php echo $AppUI->_('contactsDelete', UI_OUTPUT_JS); ?>" )) {
		form.del.value = "<?php echo $contact_id; ?>";
		form.submit();
	}
}

function orderByName( x ){
	var form = document.changecontact;
	if (x == 'name') {
		form.contact_order_by.value = form.contact_last_name.value + ", " + form.contact_first_name.value;
	} else {
		form.contact_order_by.value = form.contact_company_name.value;
	}
}

function companyChange() {
	var f = document.changecontact;
	if ( f.contact_company.value != window.company_value ){
		f.contact_department.value = '';
	} 
}

</script>

<form name="changecontact" action="do_updatecontact.php" method="post">
	<input type="hidden" name="contact_project" value="0" />
	<input type="hidden" name="contact_unique_update" value="<?php echo uniqid(""); ?>" />
	<input type="hidden" name="updatekey" value="<?php echo $updatekey; ?>" />
	<input type="hidden" name="contact_id" value="<?php echo $contact_id; ?>" />
	<input type="hidden" name="contact_owner" value="<?php echo $row->contact_owner ? $row->contact_owner : $AppUI->user_id; ?>" />
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<tr>
	<td colspan="2">
		<table border="0" cellpadding="1" cellspacing="1">
		<tr>
			<td nowrap="nowrap">
				<strong><?php echo @$AppUI->_('Please Edit Your Contact Information Below:'); ?></strong>
			</td>
			<td>
				<input type="hidden" class="text" size="25" name="contact_first_name" value="<?php echo @$row->contact_first_name; ?>" maxlength="50" />
			</td>
		</tr>
		<tr>
			<td>
				<input type="hidden" class="text" size="25" name="contact_last_name" value="<?php echo @$row->contact_last_name; ?>" maxlength="50" <?php if ($contact_id == 0) { ?> onblur="orderByName('name')"<?php } ?> />
<!--				<a href="#" onclick="orderByName('name')">[<?php echo $AppUI->_('use in display'); ?>]</a>-->
			</td>
		</tr>
<!--		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Display Name'); ?>: </td>
			<td>-->
				<input type="hidden" class="text" size="25" name="contact_order_by" value="<?php echo @$row->contact_order_by; ?>" maxlength="50" />
<!--			</td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Private Entry'); ?>: </td>
			<td>
				<input type="checkbox" value="1" name="contact_private" <?php echo (@$row->contact_private ? 'checked' : ''); ?> />
			</td>
		</tr>-->
		</table>
	</td>
<!--	<td valign="top" align="right">
		<table border="0" cellpadding="1" cellspacing="1">
		<tr>
			<td align="right" width="100" nowrap="nowrap"><?php echo $AppUI->_('Waiting Update'); ?>?:</td>
			<td>
				<input type="checkbox" value="1" name="contact_updateasked" <?php echo @$row->contact_updatekey ? 'checked="checked"' : ''; ?> />
			</td>
		</tr>	
		</table>
		<table border="0" cellpadding="1" cellspacing="1">
		<tr>
<?php
$lastupdated = new CDate($row->contact_lastupdate);
?>
			<td align="right" width="100" nowrap="nowrap"><?php echo $AppUI->_('Last Update'); ?>:</td>
			<td align="center" nowrap="nowrap"><?php echo @$row->contact_lastupdate ? $lastupdated->format($df) : ''; ?></td>
		</tr>	
		</table>
	</td>-->
</tr>
	<td valign="top" width="50%">
		<table border="0" cellpadding="1" cellspacing="1" class="details" width="100%">
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Job Title'); ?>:</td>
			<td nowrap="nowrap">
				<input type="text" class="text" name="contact_job" value="<?php echo @$row->contact_job; ?>" maxlength="100" size="25" />
			</td>
		</tr>
<!--		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Company'); ?>:</td>
			<td nowrap="nowrap">
				<input type="text" class="text" name="contact_company_name" value="<?php
echo $company_detail['company_name'];
?>" maxlength="100" size="25" />
				<input type="button" class="button" value="<?php echo $AppUI->_('select company...'); ?>..." onclick="popCompany()" />
				<input type='hidden' name='contact_company' value="<?php echo $company_detail['company_id']; ?>">
				<a href="#" onclick="orderByName('company')">[<?php echo $AppUI->_('use in display'); ?>]</a>
				</td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Department'); ?>:</td>
			<td nowrap="nowrap">
				<input type="text" class="text" name="contact_department_name" value="<?php echo $dept_detail['dept_name']; ?>" maxlength="100" size="25" />

				<input type='hidden' name='contact_department' value='<?php echo $dept_detail['dept_id']; ?>'>
				<input type="button" class="button" value="<?php echo $AppUI->_('select department...'); ?>" onclick="popDepartment()" />
				</td>
		</tr>-->
		<tr>
			<td align="right"><?php echo $AppUI->_('Title'); ?>:</td>
			<td><input type="text" class="text" name="contact_title" value="<?php echo @$row->contact_title; ?>" maxlength="50" size="25" /></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Type'); ?>:</td>
			<td><input type="text" class="text" name="contact_type" value="<?php echo @$row->contact_type; ?>" maxlength="50" size="25" /></td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Address'); ?>1:</td>
			<td><input type="text" class="text" name="contact_address1" value="<?php echo @$row->contact_address1; ?>" maxlength="60" size="25" /></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Address'); ?>2:</td>
			<td><input type="text" class="text" name="contact_address2" value="<?php echo @$row->contact_address2; ?>" maxlength="60" size="25" /></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('City'); ?>:</td>
			<td><input type="text" class="text" name="contact_city" value="<?php echo @$row->contact_city; ?>" maxlength="30" size="25" /></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('State'); ?>:</td>
			<td><input type="text" class="text" name="contact_state" value="<?php echo @$row->contact_state; ?>" maxlength="30" size="25" /></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Postcode') . ' / ' . $AppUI->_('Zip'); ?>:</td>
			<td><input type="text" class="text" name="contact_zip" value="<?php echo @$row->contact_zip; ?>" maxlength="11" size="25" /></td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Country'); ?>:</td>
			<td><input type="text" class="text" name="contact_country" value="<?php echo @$row->contact_country; ?>" maxlength="30" size="25" /></td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Work Phone'); ?>:</td>
			<td>
				<input type="text" class="text" name="contact_phone" value="<?php echo @$row->contact_phone; ?>" maxlength="30" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Home Phone'); ?>:</td>
			<td>
				<input type="text" class="text" name="contact_phone2" value="<?php echo @$row->contact_phone2; ?>" maxlength="30" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Fax'); ?>:</td>
			<td>
				<input type="text" class="text" name="contact_fax" value="<?php echo @$row->contact_fax; ?>" maxlength="30" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Mobile Phone'); ?>:</td>
			<td>
				<input type="text" class="text" name="contact_mobile" value="<?php echo @$row->contact_mobile; ?>" maxlength="30" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right" width="100"><?php echo $AppUI->_('Email'); ?>:</td>
			<td nowrap="nowrap">
				<input type="text" class="text" name="contact_email" value="<?php echo @$row->contact_email; ?>" maxlength="255" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Email'); ?>2:</td>
			<td>
				<input type="text" class="text" name="contact_email2" value="<?php echo @$row->contact_email2; ?>" maxlength="255" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Home Page'); ?>2:</td>
			<td>
				<input type="text" class="text" name="contact_url" value="<?php echo @$row->contact_url; ?>" maxlength="255" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right">Jabber:</td>
			<td>
				<input type="text" class="text" name="contact_jabber" value="<?php echo @$row->contact_jabber; ?>" maxlength="255" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right">ICQ:</td>
			<td>
				<input type="text" class="text" name="contact_icq" value="<?php echo @$row->contact_icq; ?>" maxlength="20" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right">AOL:</td>
			<td>
				<input type="text" class="text" name="contact_aol" value="<?php echo @$row->contact_aol; ?>" maxlength="20" size="25" />
			</td>
                </tr>
		<tr>
			<td align="right">MSN:</td>
			<td>
				<input type="text" class="text" name="contact_msn" value="<?php echo @$row->contact_msn; ?>" maxlength="255" size="25" />
			</td>
		</tr>
		<tr>
			<td align="right">Yahoo:</td>
			<td>
				<input type="text" class="text" name="contact_yahoo" value="<?php echo @$row->contact_yahoo; ?>" maxlength="255" size="25" />
			</td>
		</tr>
		</tr>
		<tr>
			<td align="right"><?php echo $AppUI->_('Birthday'); ?>:</td>
			<td nowrap="nowrap">
				<input type="text" class="text" name="contact_birthday" value="<?php echo @substr($row->contact_birthday, 0, 10); ?>" maxlength="10" size="25" />(<?php echo $AppUI->_('yyyy-mm-dd'); ?>)
			</td>
		</tr>
		
		<tr>
			<td align="right" colspan="3">
			<?php
require_once W2P_BASE_DIR . '/classes/CustomFields.class.php';
$custom_fields = new CustomFields('contacts', 'addedit', @$row->contact_id, "edit", 1);
$custom_fields->printHTML();
?>
			</td>
		</tr>
		
		</table>
	</td>
	<td valign="top" width="50%">
		<strong><?php echo $AppUI->_('Contact Notes'); ?></strong><br />
		<textarea class="textarea" name="contact_notes" rows="20" cols="40"><?php echo @$row->contact_notes; ?></textarea></td>
	</td>
</tr>
<tr>
	<td colspan="2">
<!--		<input type="button" value="<?php echo $AppUI->_('back'); ?>" class="button" onclick="javascript:window.location='./index.php?m=contacts';" />-->
	</td>
	<td align="right">
		<input type="button" value="<?php echo $AppUI->_('submit'); ?>" class="button" onclick="submitIt()" />
	</td>
</tr>
</form>
</table>
<?php
if (function_exists('styleRenderBoxTop')) {
	echo styleRenderBoxBottom();
}
?>