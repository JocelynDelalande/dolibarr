<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file       htdocs/install/repair.php
 *      \brief      Run repair script
 */

include_once 'inc.php';
if (file_exists($conffile)) include_once $conffile;
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';
require_once $dolibarr_main_document_root.'/core/class/extrafields.class.php';
require_once 'lib/repair.lib.php';

$grant_query='';
$etape = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
@set_time_limit(120);
error_reporting($err);

$setuplang=GETPOST("selectlang",'',3)?GETPOST("selectlang",'',3):'auto';
$langs->setDefaultLang($setuplang);
$versionfrom=GETPOST("versionfrom",'',3)?GETPOST("versionfrom",'',3):(empty($argv[1])?'':$argv[1]);
$versionto=GETPOST("versionto",'',3)?GETPOST("versionto",'',3):(empty($argv[2])?'':$argv[2]);

$langs->load("admin");
$langs->load("install");

if ($dolibarr_main_db_type == "mysql") $choix=1;
if ($dolibarr_main_db_type == "mysqli") $choix=1;
if ($dolibarr_main_db_type == "pgsql") $choix=2;
if ($dolibarr_main_db_type == "mssql") $choix=3;


dolibarr_install_syslog("repair: Entering upgrade.php page");
if (! is_object($conf)) dolibarr_install_syslog("repair: conf file not initialized",LOG_ERR);


/*
 * View
*/

pHeader('',"upgrade2",GETPOST('action'));

$actiondone=0;

// Action to launch the repair script
$actiondone=1;

print '<h3>'.$langs->trans("Repair").'</h3>';

print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
$error=0;

// If password is encoded, we decode it
if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
{
    require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
    if (preg_match('/crypted:/i',$dolibarr_main_db_pass))
    {
        $dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
        $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
        $dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
    }
    else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
}

// $conf is already instancied inside inc.php
$conf->db->type = $dolibarr_main_db_type;
$conf->db->host = $dolibarr_main_db_host;
$conf->db->port = $dolibarr_main_db_port;
$conf->db->name = $dolibarr_main_db_name;
$conf->db->user = $dolibarr_main_db_user;
$conf->db->pass = $dolibarr_main_db_pass;

// For encryption
$conf->db->dolibarr_main_db_encryption	= isset($dolibarr_main_db_encryption)?$dolibarr_main_db_encryption:'';
$conf->db->dolibarr_main_db_cryptkey	= isset($dolibarr_main_db_cryptkey)?$dolibarr_main_db_cryptkey:'';

$db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

if ($db->connected == 1)
{
    print '<tr><td nowrap="nowrap">';
    print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td align=\"right\">".$langs->trans("OK")."</td></tr>";
    dolibarr_install_syslog("repair: ".$langs->transnoentities("ServerConnection")." : $dolibarr_main_db_host ".$langs->transnoentities("OK"));
    $ok = 1;
}
else
{
    print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->transnoentities("Error")."</td></tr>";
    dolibarr_install_syslog("repair: ".$langs->transnoentities("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name));
    $ok = 0;
}

if ($ok)
{
    if($db->database_selected == 1)
    {
        print '<tr><td nowrap="nowrap">';
        print $langs->trans("DatabaseConnection")." : ".$dolibarr_main_db_name."</td><td align=\"right\">".$langs->trans("OK")."</td></tr>";
        dolibarr_install_syslog("repair: Database connection successfull : $dolibarr_main_db_name");
        $ok=1;
    }
    else
    {
        print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->trans("Error")."</td></tr>";
        dolibarr_install_syslog("repair: ".$langs->transnoentities("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name));
        $ok=0;
    }
}

// Affiche version
if ($ok)
{
    $version=$db->getVersion();
    $versionarray=$db->getVersionArray();
    print '<tr><td>'.$langs->trans("ServerVersion").'</td>';
    print '<td align="right">'.$version.'</td></tr>';
    dolibarr_install_syslog("repair: ".$langs->transnoentities("ServerVersion")." : $version");
    //print '<td align="right">'.join('.',$versionarray).'</td></tr>';
}

// Force l'affichage de la progression
print '<tr><td colspan="2">'.$langs->trans("PleaseBePatient").'</td></tr>';
flush();


// Run repair SQL file
if ($ok)
{
    $dir = "mysql/migration/";

    $filelist=array();
    $i = 0;
    $ok = 0;

    // Recupere list fichier
    $filesindir=array();
    $handle=opendir($dir);
    if (is_resource($handle))
    {
        while (($file = readdir($handle))!==false)
        {
            if (preg_match('/\.sql$/i',$file)) $filesindir[]=$file;
        }
    }
    sort($filesindir);

    foreach($filesindir as $file)
    {
        if (preg_match('/repair/i',$file))
        {
            $filelist[]=$file;
        }
    }

    // Loop on each file
    foreach($filelist as $file)
    {
        print '<tr><td nowrap>';
        print $langs->trans("Script").'</td><td align="right">'.$file.'</td></tr>';

        $name = substr($file, 0, dol_strlen($file) - 4);

        // Run sql script
        $ok=run_sql($dir.$file, 0, '', 1);
    }
}


// Search list of fields declared and list of fields created into databases and create fields missing
$extrafields=new ExtraFields($db);
$listofmodulesextra=array('societe'=>'company','adherent'=>'member','product'=>'product');
foreach($listofmodulesextra as $tablename => $elementtype)
{
    // Get list of fields
    $tableextra=MAIN_DB_PREFIX.$tablename.'_extrafields';

    // Define $arrayoffieldsdesc
    $arrayoffieldsdesc=$extrafields->fetch_name_optionals_label($elementtype);

    // Define $arrayoffieldsfound
    $arrayoffieldsfound=array();
    $resql=$db->DDLDescTable($tableextra);
    if ($resql)
    {
        print '<tr><td>Check availability of extra field for '.$tableextra."<br>\n";
        $i=0;
        while($obj=$db->fetch_object($resql))
        {
            $fieldname=$fieldtype='';
            if (preg_match('/mysql/',$db->type))
            {
                $fieldname=$obj->Field;
                $fieldtype=$obj->Type;
            }
            else
            {
                $fieldname = isset($obj->Key)?$obj->Key:$obj->attname;
                $fieldtype = isset($obj->Type)?$obj->Type:'varchar';
            }

            if (empty($fieldname)) continue;
            if (in_array($fieldname,array('rowid','tms','fk_object','import_key'))) continue;
            $arrayoffieldsfound[$fieldname]=array('type'=>$fieldtype);
        }

        // If it does not match, we create fields
        foreach($arrayoffieldsdesc as $code => $label)
        {
            if (! in_array($code,array_keys($arrayoffieldsfound)))
            {
                print 'Found field '.$code.' declared into '.MAIN_DB_PREFIX.'extrafields table but not found into desc of table '.$tableextra." -> ";
                $type=$extrafields->attribute_type[$code]; $value=$extrafields->attribute_size[$code]; $attribute=''; $default=''; $extra=''; $null='null';
                $field_desc=array(
                	'type'=>$type,
                	'value'=>$value,
                	'attribute'=>$attribute,
                	'default'=>$default,
                	'extra'=>$extra,
                	'null'=>$null
                );
                //var_dump($field_desc);exit;

                $result=$db->DDLAddField($tableextra,$code,$field_desc,"");
                if ($result < 0)
                {
                    print "KO ".$db->lasterror."<br>\n";
                }
                else
                {
                    print "OK<br>\n";
                }
            }
        }

        print "</td><td>&nbsp;</td></tr>\n";
    }
}


// Clean data into ecm_directories table
clean_data_ecm_directories();


// Check and clean linked elements
if (GETPOST('clean_linked_elements'))
{
	// propal => order
	print "</td><td>".checkLinkedElements('propal', 'commande')."</td></tr>\n";

	// propal => invoice
	print "</td><td>".checkLinkedElements('propal', 'facture')."</td></tr>\n";

	// order => invoice
	print "</td><td>".checkLinkedElements('commande', 'facture')."</td></tr>\n";

	// order => shipping
	print "</td><td>".checkLinkedElements('commande', 'shipping')."</td></tr>\n";

	// shipping => delivery
	print "</td><td>".checkLinkedElements('shipping', 'delivery')."</td></tr>\n";

	// order_supplier => invoice_supplier
	print "</td><td>".checkLinkedElements('order_supplier', 'invoice_supplier')."</td></tr>\n";
}


// Run purge of directory
if (GETPOST('purge'))
{
    $conf->setValues($db);

    $listmodulepart=array('company','invoice','invoice_supplier','propal','order','order_supplier','contract','tax');
    foreach ($listmodulepart as $modulepart)
    {
        $filearray=array();
        $upload_dir = isset($conf->$modulepart->dir_output)?$conf->$modulepart->dir_output:'';
        if ($modulepart == 'company') $upload_dir = $conf->societe->dir_output; // TODO change for multicompany sharing
        if ($modulepart == 'invoice') $upload_dir = $conf->facture->dir_output;
        if ($modulepart == 'invoice_supplier') $upload_dir = $conf->fournisseur->facture->dir_output;
        if ($modulepart == 'order') $upload_dir = $conf->commande->dir_output;
        if ($modulepart == 'order_supplier') $upload_dir = $conf->fournisseur->commande->dir_output;
        if ($modulepart == 'contract') $upload_dir = $conf->contrat->dir_output;

        if (empty($upload_dir)) continue;

        print '<tr><td colspan="2">Clean orphelins files into files '.$upload_dir.'</td></tr>';

        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','\.meta$','^temp$','^payments$','^CVS$','^thumbs$'),'',SORT_DESC,1);

        // To show ref or specific information according to view to show (defined by $module)
        if ($modulepart == 'invoice')
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $object_instance=new Facture($db);
        }
        else if ($modulepart == 'invoice_supplier')
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
            $object_instance=new FactureFournisseur($db);
        }
        else if ($modulepart == 'propal')
        {
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
            $object_instance=new Propal($db);
        }
        else if ($modulepart == 'order')
        {
            include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $object_instance=new Commande($db);
        }
        else if ($modulepart == 'order_supplier')
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
            $object_instance=new CommandeFournisseur($db);
        }
        else if ($modulepart == 'contract')
        {
            include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
            $object_instance=new Contrat($db);
        }
        else if ($modulepart == 'tax')
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
            $object_instance=new ChargeSociales($db);
        }

        $var=true;
        foreach($filearray as $key => $file)
        {
            if (!is_dir($file['name'])
            && $file['name'] != '.'
            && $file['name'] != '..'
            && $file['name'] != 'CVS'
            )
            {
                // Define relative path used to store the file
                $relativefile=preg_replace('/'.preg_quote($upload_dir.'/','/').'/','',$file['fullname']);

                //var_dump($file);
                $id=0; $ref=''; $object_instance->id=0; $object_instance->ref=''; $label='';

                // To show ref or specific information according to view to show (defined by $module)
                if ($modulepart == 'invoice')          {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'invoice_supplier') {
                    preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=$reg[1];
                }
                if ($modulepart == 'propal')           {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'order')            {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'order_supplier')   {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'contract')         {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'tax')              {
                    preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=$reg[1];
                }

                if ($id || $ref)
                {
                    //print 'Fetch '.$id.' or '.$ref.'<br>';
                    $result=$object_instance->fetch($id,$ref);
                    //print $result.'<br>';
                    if ($result == 0)    // Not found but no error
                    {
                        // Clean of orphelins directories are done into repair.php
                        print '<tr><td colspan="2">';
                        print 'Delete orphelins file '.$file['fullname'].'<br>';
                        if (GETPOST('purge') == 2)
                        {
                            dol_delete_file($file['fullname'],1,1,1);
                            dol_delete_dir(dirname($file['fullname']),1);
                        }
                        print "</td></tr>";
                    }
                    else if ($result < 0) print 'Error in '.get_class($object_instance).'.fetch of id'.$id.' ref='.$ref.', result='.$result.'<br>';
                }
            }
        }
    }
}

print '</table>';



if (empty($actiondone))
{
    print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}


print '<center><a href="../index.php?mainmenu=home'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
print $langs->trans("GoToDolibarr");
print '</a></center>';

pFooter(1,$setuplang);

if ($db->connected) $db->close();

// Return code if ran from command line
if (! $ok && isset($argv[1])) exit(1);
?>