<?php
/**
 * ILIAS REST Plugin for the ILIAS LMS
 *
 * Authors: D.Schaefer and T.Hufschmidt <(schaefer|hufschmidt)@hrz.uni-marburg.de>
 * Since 2014
 */
namespace RESTController\extensions\admin_v1;



require_once('Services/User/classes/class.ilObjUser.php');
require_once('Services/Object/classes/class.ilObjectFactory.php');

class WorkspaceAdminModel extends Libs\RESTModel
{
    public $tree;
    public $fields_of_interest = array("file"); // ,blog

    /**
     * This method determines all users that have
     * content in their workspace.
     * @param $limit
     * @param $offset
     * @return assoc_array: num all users, num users ws, num users with shared items
     */
    public function scanUsersForWorkspaces($limit, $offset)
    {
        include_once('Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php');
        $r = array();
        $usersWithWorkspace = 0;
        $selFields = $this->fields_of_interest;
        $r['fields'] = $selFields;
        $list = \ilObjUser::_getAllUserData(array("login"),1); // note: the field usr_id is always included.
        $r['numAllUsers'] = count($list);
        $a_ws = array();
        foreach($list as $user) {
            $user_id =  $user['usr_id'];
            $this->tree = new \ilWorkspaceTree($user_id);
            if ($this->tree->readRootId()) {
                $cntItems = count($this->tree->getSubTree(
                    $this->tree->getNodeData($this->tree->getRootId()),
                    false, $selFields));
                if ($cntItems > 0) {
                    $usersWithWorkspace++;
                    $a_uws = array();
                    $a_uws['numItems'] = $cntItems;
                    $a_uws['usr_id'] = $user_id;
                    $a_ws[] = $a_uws;
                }
            }
        }

        $numContent = array();
        foreach ($a_ws as $key => $row) {
            $numContent[$key] = $row['numItems'];
        }
        array_multisort($numContent, SORT_DESC, $a_ws);

        $partial = array();
        $l_end = min($offset + $limit,$usersWithWorkspace);
        for ($i = $offset; $i < $l_end; $i++) {
            $partial[] = $a_ws[$i];
        }
        $r['workspaces'] =  $partial;
        $r['numWorkspaces'] = $usersWithWorkspace;
        return $r;
    }

    /**
     * This method returns a list of items from a particular user workspace of a type
     * internally specified by $fields_of_interest.
     *
     * This is a hopefully more efficient rewrite of the methods below.
     * @param $user_id
     * @return array of items
     */
    public function getUserWorkspaceItems($user_id)
    {
        $r = array();
        $selFields = $this->fields_of_interest;
        include_once('Services/PersonalWorkspace/classes/class.ilWorkspaceTree.php');
        $this->tree = new \ilWorkspaceTree($user_id);
        if ($this->tree->readRootId()) {
            $nodes = $this->tree->getSubTree(
                $this->tree->getNodeData($this->tree->getRootId()),
                false, $selFields);
            $content=array();
            $allTreeData = $this->tree->getSubTree(
                $this->tree->getNodeData($this->tree->getRootId()),
                true, $selFields);

            foreach ($allTreeData as $item) {
                $itemContent['obj_id'] = $item['obj_id'];
                $itemContent['title'] = $item['title'];
                $itemContent['type'] = $item['type'];
                $itemContent['create_date'] = $item['create_date'];
                $itemContent['last_update'] = $item['last_update'];
                if ($item['type'] == "file") {
                    $fileObj=\ilObjectFactory::getInstanceByObjId($item['obj_id']);
                    $itemContent['file_name'] = $fileObj->getFileName();
                    $itemContent['file_size']= $fileObj->getFileSize();
                }
                $content[]=$itemContent;
            }

            $r['content']=$content;
        }
        return $r;
    }

    /**
     * Only for development purposes
     * @param $limit
     * @param $offset
     * @return array
     */
    public function scanDummyWorkspaces($limit, $offset)
    {
        $r=array();
        $usersWithWorkspace = 500;
        $r['numWorkspaces'] = $usersWithWorkspace;
        $l_start = $offset;
        $l_end = $limit+$offset;
        if ($l_end > $usersWithWorkspace) {
            $l_end = $usersWithWorkspace;
        }
        $a_ws = array();
        $cnt_entries = 0;
        for ($i = $l_start; $i < $l_end; $i++) {
            $a_uws = array();
            $a_uws['numItems'] = rand(3,20);
            $a_uws['usr_id'] = $i+rand(1,4);
            $a_ws[] = $a_uws;
            $cnt_entries = $cnt_entries + 1;
        }

        // http://stackoverflow.com/questions/1597736/how-to-sort-an-array-of-associative-arrays-by-value-of-a-given-key-in-php
        $numContent = array();
        foreach ($a_ws as $key => $row) {
            $numContent[$key] = $row['numItems'];
        }
        array_multisort($numContent, SORT_DESC, $a_ws);

        $r['numEntries'] = $cnt_entries;
        $r['workspaces'] =  $a_ws;
        return $r;
    }
}
