/*
$cats = \ilCalendarCategories::_getInstance($userId);
$cats->initialize(\ilCalendarCategories::MODE_MANAGE);
foreach($cats->getCategoriesInfo() as $category) {
  echo 'Cat: ' . $category['title'] . "\n";

  $id = (int) $category['cat_id'];
  $subCats = $cats->getSubitemCategories($id);
  $apps = \ilCalendarCategoryAssignments::_getAssignedAppointments($subCats);
  foreach($apps as $cal_entry_id) {
       $entry = new \ilCalendarEntry($cal_entry_id);
       echo 'Entry: ' . $entry->getTitle() . " / " . $entry->getPresentationTitle() . "\n";
  }
}
*/


/*
public function parse()
	{
		global $ilUser, $tree;

		include_once('./Services/Calendar/classes/class.ilCalendarCategories.php');
		$cats = ilCalendarCategories::_getInstance($ilUser->getId());
		$cats->initialize(ilCalendarCategories::MODE_MANAGE);

		$tmp_title_counter = array();
		$categories = array();
		foreach($cats->getCategoriesInfo() as $category)
		{
			$tmp_arr['obj_id'] = $category['obj_id'];
			$tmp_arr['id'] = $category['cat_id'];
			$tmp_arr['title'] = $category['title'];
			$tmp_arr['type'] = $category['type'];

			// Append object type to make type sortable
			$tmp_arr['type_sortable'] = ilCalendarCategory::lookupCategorySortIndex($category['type']);
			if($category['type'] == ilCalendarCategory::TYPE_OBJ)
			{
				$tmp_arr['type_sortable'] .= ('_'.ilObject::_lookupType($category['obj_id']));
			}

			$tmp_arr['color'] = $category['color'];
			$tmp_arr['editable'] = $category['editable'];
			$tmp_arr['accepted'] = $category['accepted'];
			$tmp_arr['remote'] = $category['remote'];

			$categories[] = $tmp_arr;

			// count title for appending the parent container if there is more than one entry.
			$tmp_title_counter[$category['type'].'_'.$category['title']]++;
		}

		$path_categories = array();
		foreach($categories as $cat)
		{
			if($cat['type'] == ilCalendarCategory::TYPE_OBJ)
			{
				if($tmp_title_counter[$cat['type'].'_'.$cat['title']] > 1)
				{
					foreach(ilObject::_getAllReferences($cat['obj_id']) as $ref_id)
					{
						include_once './Services/Tree/classes/class.ilPathGUI.php';
						$path = new ilPathGUI();
						$path->setUseImages(false);
						$path->enableTextOnly(false);
						$cat['path'] = $path->getPath(ROOT_FOLDER_ID, $ref_id);
						break;
					}
				}
			}
			$path_categories[] = $cat;
		}
		$this->setData($path_categories);
	}
*/
