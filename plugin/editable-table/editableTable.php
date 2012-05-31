<?php
/**
 * It is assumed that any table that extends editableTable has:
 * id
 * name
 * create_user_id
 * create_date
 * modify_user_id
 * modify_date
 * 
 * It is also assumed that there is a 'user' table.
 * 
 * Your editable tables must and columns must be named in lower_underscore_format
 * 
 * Your foreign tables must be stored in the first table simply as the foreign table's name,
 * eg:
 * 
 * user
 * ----
 * id
 * name
 * address
 * ...
 * 
 * address
 * -------
 * id
 * name
 * ....
 * 
 * 
 * you can create a yourtableRow class which extends row, and it's displayColumnName and
 * editColumnName functions will be used instead of the defaults listed here.
 * 
 * @author Dean Rather 2012
 *
 */
class editableTable extends table {
	
	/**
	 * Overwrite this with a description of this table's data
	 * eg: public $data = array('name'=>'string', 'age'=>'int');
	 * Supported types are:
	 * - string
	 * - int
	 * - text
	 * - _<foreign 1-many table name>
	 * - __<foreign many-many table name>
	 * The key is the column name, unless it is a foreign key.
	 * If the row represents a foreign key, set the key to whatever you want to display, and set the value to
	 * an underscore or two with the foreign table name.
	 * eg: 
	 * 'id' 		=> 'int',
	 * 'name'		=> 'string',
	 * 'type'		=> '_type',	// people have 1 'type' from the 'type' table. this data is stored in the 'type_id' column
	 * 'projects'	=> '__project' // there is a person_project table, with person_id and project_id columns
	 * 
	 */
	public $data = array();
	public $key = 'id'; // we don't support any other keys
	public $dontDisplayColumns = array(); // use ->dontDisplayColumn('columnName')
	public $dontInlineEdit = false; // use ->dontInlineEdit()
	
	/******************************************************
						Row Display Functions
	******************************************************/
	
	
	
	/**
	 * Displays a table's header row
	 */
	public function headerRow(){
		$return = "<tr><th>#</th>";
		
		foreach($this->data as $colName => $dataType)
		{
			if($dataType=='text') continue;
			if(in_array($colName, $this->dontDisplayColumns)) continue;
			$return .= "<th>".$this->fancify($colName)."</th>";
		}
		
		$return .= "<th>Action</th></tr>";
		return $return;
	}

	public function paginationRow($offset=null, $limit=null){
		if($offset===null) $offset=$this->itemsPerPage;
		if($limit===null) $limit=$this->itemsPerPage;
		$cols=2;
		foreach($this->data as $colName => $dataType)
		{
			if($dataType=='text') continue;
			if(in_array($colName, $this->dontDisplayColumns)) continue;
			$cols++;
		}
		$action = '/'.$this->controller->core->uri[0].'/partial/';
		
		$return = "
			<tr action='$action' class='pagination'>
				<td colspan='$cols'>
					<input type='hidden' name='offset' value='$offset' >
					<input type='hidden' name='limit' value='$limit' >
					<a href='#' class='btn'>Load More...</a>
				</td>
			</tr>
		";
		return $return;
	}
	
//	public function filterRow(){
//		$action = '/'.$this->controller->core->uri[0].'/partial/';
//		$return = "<tr action='$action' class='filtration'>";
//		$return .= '<td><b>FILTER</b></td>';
//		
//		$row = $this->createRow();
//		
//		foreach($this->data as $colName => $dataType) {
//			
//			if(in_array($colName, $this->dontDisplayColumns)) continue;
//			if($colName != 'worlds') { $return.='<td></td>'; continue;} // Change this some day to not be so shit
//		
//			$return .= "<td>";
//			$return .= $this->editAttribute($row, $colName, $dataType);
//			$return .= "</td>";
//		}
//		
//		$return .= '<td>';;
//		$return .= '</td>';
//		$return .= "</tr>";
//		return $return;
//	}
	
	/**
	 * Display's a table's edit row
	 * @param $row the row to display for editing
	 */
	public function editRow($row=false) {
		
		$action = '/'.$this->controller->core->uri[0].'/edit/';
		$return = "<tr action='$action' class='editableRow'>";
		$return .= "<td>";
		
		$id = 0;
		$idName = "New";
		if($row) {
			$id = $row->id;
			$idName = "#$id";
		} else {
			$row = $this->createRow();
		}
		$return .= "<input type='hidden' value='$id' name='id'>$idName</td>";
		
		foreach($this->data as $colName => $dataType) {
			
			if(in_array($colName, $this->dontDisplayColumns)) continue;
			if($dataType=='text') continue;
		
			$return .= "<td>";
			$return .= $this->editAttribute($row, $colName, $dataType);
			$return .= "</td>";
		}
		
		if($id){
			$return .= '<td><button data-loading-text="saving..." class="save-btn btn btn-primary">save</button></td>';
		} else {
			
			if($this->dontInlineEdit)
			{
				$return .= '<input type="hidden" name="_dontInlineEdit" value="true">'; 
			}
			$return .= '<td><button data-loading-text="adding..." class="add-btn btn btn-primary">add</button></td>';
		}
		
		$return .= '</tr>';
		return $return;
	}
	
	
	/**
	 * Displays a regular table row
	 * @param $row the row to display
	 */
	public function displayRow($row=false) {
		
		$source = '/'.$this->controller->core->uri[0];
		$return = "<tr source='$source/get/' class='editableRow'>";
		
		$id = $row->id;
		$return .= "<td>#$id</td><input type='hidden' name='id' value='$id'>";
		
		foreach($this->data as $colName => $dataType) {
			if(in_array($colName, $this->dontDisplayColumns)) continue;
			if($dataType=='text') continue;
			$return .= "<td class='col-$colName'>";
			$return .= $this->displayAttribute($row, $colName, $dataType);
			$return .= "</td>";
		}
		
		if($this->dontInlineEdit)
		{
			$return .= "<td><a class='btn btn-primary' href='$source/edit/$row->id'>edit</a></td>";
		}
		else
		{
			$return .= '<td><button data-loading-text="loading..." class="edit-btn btn btn-primary">edit</button></td>';
		}
		
		$return .= '</tr>';
		return $return;
	}
	
	/**
	 * Displays an attribute of the row
	 * @param Row $row a row object to display the data from
	 * @param String $colName the column name, eg: name, email_address
	 * @param String $dataType The type, eg. string, int, _tableName, See editableTable's $data for definition
	 */
	public function displayAttribute($row, $colName, $dataType=false, $config=array()){
	
		if(!$dataType){
			$dataType = isset_val($this->data[$colName]);
			if(!$dataType) $this->controller->core->error("you must define [$colName]s type in \$data");
		}
		
		// eg $row->displayName()
		$function = 'display'.str_replace(' ', '', $this->fancify($colName));
		if(method_exists($row, $function))
		{
			return $row->$function();
		}
		else
		{
			switch($dataType){
				case 'string':	return $this->displayString($row, $colName, $config);	break;
				case 'int':		return $this->displayInt($row, $colName, $config);		break;
				case 'text':	return $this->displayText($row, $colName, $config);		break;
				case 'password':	return $this->displayPassword($row, $colName, $config);		break;
					
				default:
					
					if(left(2, $dataType)=='__') {
						$config = array('colName' => $colName);
						return $this->displayTableMulti($row, $dataType, $config);
					}
					
					if($dataType[0]=='_') return $this->displayTable($row, $dataType, $config);
			}
		}
		return false;
	}

	function editAttribute($row, $colName, $dataType=false, $config=array()){
	
		if(!$dataType){
			$dataType = isset_val($this->data[$colName]);
			if(!$dataType) $this->controller->core->error("you must define [$colName]s type in \$data");
		}
		
		$function = 'edit'.str_replace(' ', '', $this->fancify($colName));
		if(method_exists($row, $function))
		{
			return $row->$function();
		}
		else
		{
			switch($dataType){
				case 'string':	return $this->editString($row, $colName, $config);	break;
				case 'int':		return $this->editInt($row, $colName, $config);		break;
				case 'text':	return $this->editText($row, $colName, $config);	break;
				case 'password':	return $this->editPassword($row, $colName, $config);		break;
				
				default:
					
					if(left(2, $dataType) == '__') {
						$config['label'] = $colName;
						return $this->editTableMulti($row, $dataType, $config);
					}
					
					if(left(1, $dataType) == '_') {
						$config['colName'] = $colName;
						return $this->editTable($row, $dataType, $config);
					}
			}
		}
	}
	
	/******************************************************
						Display Data
	******************************************************/
	
	
	
	/**
	 * Used to format a string for display
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function displayString($row, $colName, $config=array()){
		return $row->$colName;
	}
	
	
	/**
	 * Used to format an int for display
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function displayInt($row, $colName, $config=array()){
		$value = $row->$colName;
		
		$this->cacheNextQuery();
		$result = $this->query("SELECT MIN($colName), MAX($colName) FROM $this->table");
		$min = $result[0]["MIN($colName)"];
		$max = $result[0]["MAX($colName)"];
		if(!$max) $max=1;
		
		$percent = ($value/$max)*100;
		
		$class = isset_val($config['class']);
		if(!$class) $class = 'progress-info';
		
		$html = <<<HTML
<div class="progress $class table-display-int">
  <div class="bar" style="width: $percent%;">$value</div>
</div>
HTML;
		
		return $html;
		
	}
	
	/**
	 * Used to format a string for display
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function displayText($row, $colName, $config=array()){
		return "<div class='text well'>".$this->controller->view->markdown($row->$colName)."</div>";
	}
	
	/**
	 * Used to format a password for display
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function displayPassword($row, $colName, $config=array()){
		return $row->$colName;
	}
	

	/**
	 * Used to format a foreign key to a separate table for display.
	 * Automatically gets the 'id' and 'name' column from that table
	 * @param row $row
	 * @param string $colName the column of the other table
	 */
	public function displayTable($row, $colName, $config=false){
		$colName = trim($colName, '_');
		$tableName = $colName;
		$colName .= '_id';
		$value = isset_val($row->$colName);
		$default = $row->getDefault($colName);
		if(!$value) $value = $default;
		if(!$value) return '';
		
		$this->cacheNextQuery();
		$result = $this->query("SELECT id, name FROM $tableName WHERE id=$value");
		if(!isset($result[0])) return '';
		$foreignRow=$result[0];
		$valueName = '';
//		foreach($result as $foreignRow) {
			$id = $foreignRow['id'];
			$name = $foreignRow['name'];
			if($id==$value) $valueName = $name;
//		}
		
		$class = isset_val($config['class']);
		if(!$class) $class = '';
		
		$href=isset_true($config['href']);
		if($href) $href = "href='/$tableName/$id'";
		
		return "<a class='btn $class' $href>$valueName</a>";
	}

	/**
	 * Used to display selections from a foreign table
	 * Automatically queries the other table for 'id' and 'name' and provides a display
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function displayTableMulti($row, $colName, $config=false){
		$otherTable = substr($colName, 2, strlen($colName));
		
		$result = $row->getAttachedTable($otherTable);
		
		$listItems = array();
		foreach($result as $foreignRow) {
			$id = $foreignRow->id;
			$name = $foreignRow->name;
			if(method_exists($foreignRow, 'getToolTip')) $name = $foreignRow->getToolTip();
			$listItems[$id] = $name;
		}
		
		$html = implode(', ', $listItems);
		return $html;
	}
	
	
	/******************************************************
						Edit Data
	******************************************************/
	

	
	/**
	 * Used to format a string for editing
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function editString($row, $colName, $config=array()){
		$value = isset_val($row->$colName);
		$default = $row->getDefault($colName);
		if(!$value) $value = $default;
		$class = isset_val($config['class']);
		return "<input type='text' class='text-$colName $class' value='$value' name='$colName' default='$default'>";
	}
	
	/**
	 * Used to format a password for editing
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function editPassword($row, $colName, $config=array()){
		$value = isset_val($row->$colName);
		$class = isset_val($config['class']);
		if($value) $value='~unchanged~';
		return "<input type='text' class='text-$colName $class' value='$value' name='$colName'>";
	}
	
	/**
	 * Used to format an int for editing
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function editInt($row, $colName, $config=array()){
		$value = isset_val($row->$colName);
		if(!$value) $value = $row->getDefault($colName);
		if(isset($config['min']) && isset($config['max']))
		{
			$min = $config['min'];
			$max = $config['max'];
			return "<input
			 		class='table-edit-slider'
					type='range'
					name='$colName'
					value='$value'
					min='$min'
					max='$max'
					data-highlight='true'
					data-mini='true'
				/><span class='table-edit-slider-value'>$value</span>";
		}
		else
		{
			return "<input type='number' value='$value' name='$colName' class='table-edit-int'>";
		}
	}
	
	/**
	 * Used to format text for editing
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function editText($row, $colName, $config=array()){
		$value = isset_val($row->$colName);
		return "<textarea name='$colName'>$value</textarea>";
	}
	

	/**
	 * Used to format a foreign table for editing.
	 * Automatically queries the other table for 'id' and 'name' and provides a dropdown list
	 * $colName is passed in like this "_type" when the joining table is named "type" and the foreign key column is "type_id"
	 * @param row $row
	 * @param string $colName the column of that row
	 */
	public function editTable($row, $colName, $config=false){
		$colName = trim($colName, '_'); // get the underscore off the front
		$colName .= '_id'; // Add the '_id' suffix
		$tableName = right(-3, $colName); // get rid of _id off the col name
		$value = isset_val($row->$colName);
		if(!$value) $value = $row->getDefault($colName);
		
		$classes = isset_val($config['classes']);
		
		$this->cacheNextQuery();
		$result = $this->query("SELECT id, name FROM $tableName");
		
		
		$listItems = '';
		$defaultName = '';
		foreach($result as $foreignRow) {
			$id = $foreignRow['id'];
			$name = $foreignRow['name'];
			if(!$name) continue;
			if($id == $value) $defaultName = $name;
			if(!$defaultName) $defaultName = 'Please Select';
			$class = '';
			if($classes) $class = isset_val($classes[$id]);
			$listItems .= "\n<li><a href='#' value='$id' colour='$class'>$name</a></li>";
		}
		
		if($classes) $class = isset_val($classes[$value]);
		
		$html = <<<HTML
		
		<div class="btn-group table-dropdown">
		  <a class="btn dropdown-toggle $class" data-toggle="dropdown" href="#">
		  	<input type="hidden" value="$value" name="$colName">
		    <span class="display">$defaultName</span>
		    <span class="caret"></span>
		  </a>
		  <ul class="dropdown-menu">
			$listItems
		  </ul>
		</div>
HTML;
return $html;
	}
	
	
	/**
	 * Used to display options from a foreign table with the ability to checkbox any number of them
	 * Automatically queries the other table for 'id' and 'name' and provides a dropdown list
	 * @param row $row
	 * @param string $col the column of that row
	 */
	public function editTableMulti($row, $colName, $config=false){
		$otherTable = substr($colName, 2, strlen($colName));
		$thisTable = $this->table;
		$thisID = isset_val($row->id);
		if(!$thisID) $thisID = 0;
		$joinTable = $thisTable.'_'.$otherTable;
		
		$otherTableIDCol = $otherTable.'_id';
		$thisTableIDCol = $thisTable.'_id';
		
		$this->cacheNextQuery();
		$query = <<<QUERY
		SELECT
			$otherTable.id AS 'id',
			$otherTable.name AS 'name',
			(
				SELECT COUNT(1)
				FROM $joinTable
				WHERE $joinTable.$otherTableIDCol = $otherTable.id
				AND $joinTable.$thisTableIDCol=$thisID
			) AS 'checked'
		FROM $otherTable
		ORDER BY `name` ASC
QUERY;
		$result = $this->query($query);
		
		
		$listItems = '';
		foreach($result as $foreignRow) {
			$id = $foreignRow['id'];
			$name = $foreignRow['name'];
			$checked = $foreignRow['checked'];
			$checked = ($checked ? 'checked' : '');
			$fieldName = $otherTable."-list[]";
			
			$listItems .= <<<LI
                    <li>
                        <label>
                        	<input type="checkbox" name="$fieldName" value="$id" $checked>
							<p>$name</p>
						</label>
                    </li>
LI;
		}
		
		$label = false;
		if($config) $label = isset_val($config['label']);
		if(!$label) $label = $otherTable;
		$label = $this->fancify($label);
		
		
		$html = <<<HTML
		
		<div class="btn-group table-multi-dropdown">
		  <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
		    <span class="display">$label</span>
		    <span class="caret"></span>
		  </a>
		  <ul class="dropdown-menu">
			$listItems
		  </ul>
		</div>
		
HTML;
return $html;
	}
	
	
	
	
	/******************************************************
						Misc Functions
	******************************************************/
	
	
	/**
	 * Pass me in a col name and when using editRow, headerRow, and displayRow I will ignore that col.
	 * Note that if you call this function in the indexView, and you use inline editing,
	 * you would want to call it in getView as well
	 */
	public function dontDisplayColumn($columnName)
	{
		$this->dontDisplayColumns[] = $columnName;
	}
	
	/**
	 * If set, the 'edit' and 'add' buttons beside the columns takes you to the detailed edit page.
	 * Enter description here ...
	 */
	public function dontInlineEdit()
	{
		$this->dontInlineEdit = true;
	}
}