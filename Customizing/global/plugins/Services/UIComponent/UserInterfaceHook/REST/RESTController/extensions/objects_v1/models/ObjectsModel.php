<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer, S.Schneider and T. Hufschmidt <(schaefer|schneider|hufschmidt)@hrz.uni-marburg.de>
 * 2014-2015
 */
namespace RESTController\extensions\objects_v1;


class ObjectsModel
{
    public function getObject($ref, $request, &$response) {
        $a_ref_id = $ref;
           if(!is_numeric($a_ref_id))
        {
            $response->setMessage('RefID must be numeric');
            $response->setRESTCode(-100);
            return;
        }

        if(!$tmp_obj = \ilObjectFactory::getInstanceByRefId($a_ref_id,false))
        {
            $response->setMessage('Can\'t create Object');
            $response->setRESTCode(-200);
            return;
        }


        if(\ilObject::_isInTrash($a_ref_id))
        {
            $response->setMessage('Object has been deleted');
            $response->setRESTCode(0);
            return;
        }

        // Now, $tmp_obj contains *way* to much data to pass it back to the client.
        // In function __appendObject(&$object) in
        // webservice/soap/classes/class.ilObjectXMLWriter.php
        // the cherry-picking is done for relevant attributes, copied here.

        $response->setData('title', $tmp_obj->getTitle());
        $response->setData('desc', $tmp_obj->getDescription());
        $response->setData('owner', $tmp_obj->getOwner());
        $response->setData('createDate', $tmp_obj->getCreateDate());
        $response->setData('lastUpdate', $tmp_obj->getLastUpdateDate());
        $response->setData('importId', $tmp_obj->getImportId());

        $response->setMessage('success');


    }
}
