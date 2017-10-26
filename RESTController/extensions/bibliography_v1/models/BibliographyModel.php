<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\bibliography_v1;

// This allows us to use shortcuts instead of full quantifier
use \RESTController\libs as Libs;


require_once('Services/User/classes/class.ilObjUser.php');
require_once('Modules/Bibliographic/classes/class.ilObjBibliographic.php');
require_once('Modules/Bibliographic/classes/class.ilBibliographicEntry.php');
require_once('Modules/Bibliographic/classes/Types/class.ilBibTex.php');
require_once('Modules/Bibliographic/classes/Types/class.ilRis.php');


class BibliographyModel extends Libs\RESTModel
{
    /**
     * Reads the bibliography object with ref_id for a user.
     * @param $ref_id
     * @param $user_id
     * @return array
     * @throws Libs\Exceptions\Database
     * @throws Libs\Exceptions\ilObject
     */
    function getBibliography($ref_id, $user_id) {
        global $ilAccess;
        Libs\RESTilias::loadIlUser();
        global $ilUser;
        $ilUser->setId($user_id);
        $ilUser->read();
        $obj_id = Libs\RESTilias::getObjId($ref_id);

        // Check access rights
        if (!(($ilAccess->checkAccess('read', "", $ref_id) )
            || $ilAccess->checkAccess('write', "", $ref_id))
        ) {
            throw new Exceptions\BibliographyException("No r/w access.",$ref_id, "biblio");
        }

        $bibObj = new \ilObjBibliographic($obj_id);

        if ($bibObj->getOnline() == false) {
            throw new Exceptions\BibliographyException("Object is not online.",$ref_id, "biblio");
        }

        $bib_info = array();
        $bib_info['ref_id'] = $ref_id;
        if(is_null($bibObj)) {
            $bib_info['title'] = 'notFound';
        } else {
            $bib_info['title'] = $bibObj->getTitle();
            $bib_info['description'] = $bibObj->getDescription();
            $bib_info['create_date'] = $bibObj->create_date;
            $bib_info['type'] = $bibObj->getType();

            $bib_entries = array();
            foreach (\ilBibliographicEntry::getAllEntries($obj_id) as $entry) {
                $ilBibliographicEntry = ilObjBibliographicEntryLight::getInstanceLight($bibObj->getFiletype(), $entry['entry_id']);

                $b_entry = array();
                $b_entry['entry_id'] = $entry['entry_id'];
                $b_entry['content'] = BibliographyModel::getBiblioEntryDetails($ilBibliographicEntry);

                $bib_entries[] = $b_entry;
            }
            $bib_info['entries'] = $bib_entries;
        }
        //var_dump($bibObj);
        return $bib_info;

    }

    /**
     * Helper function for getBibliography()
     * Inspired by ilBibliographicDetailsGUI.
     * Todo: language files might be used in the future
     * @param $entry
     * @return mixed
     */
    static function getBiblioEntryDetails($entry) {
        $attributes = $entry->getAttributes();
        //translate array key in order to sort by those keys
        foreach ($attributes as $key => $attribute) {
            //Check if there is a specific language entry
            if (false) {
            //if ($lng->exists($key)) {
                //$strDescTranslated = $lng->txt($key);
            } //If not: get the default language entry
            else {
                $arrKey = explode("_", $key);
                $is_standard_field = false;
                switch ($arrKey[0]) {
                    case 'bib':
                        $is_standard_field = \ilBibTex::isStandardField($arrKey[2]);
                        break;
                    case 'ris':
                        $is_standard_field = \ilRis::isStandardField($arrKey[2]);
                        break;
                }
                //				var_dump($is_standard_field); // FSX
                $strDescTranslated = $arrKey[2];
               /* if ($is_standard_field) {
                    $strDescTranslated = $lng->txt($arrKey[0] . "_default_" . $arrKey[2]);
                } else {
                    $strDescTranslated = $arrKey[2];
                }*/
            }
            unset($attributes[$key]);
            $attributes[$strDescTranslated] = $attribute;
        }
        // sort attributes alphabetically by their array-key
        ksort($attributes, SORT_STRING);
        return $attributes;
    }

}
