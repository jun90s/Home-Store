<?php
/*
 * Copyright 2017 Zhang Jun <jun90s@163.com>.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Store.Home API v0.0.1
 * 2017.05.17
 */
ob_start();

/*
 * Global variable
 */
$errno = 0;
$dbh = null;
$ref['id']='';

/*
 * UUID Support
 */
function randomUUID() {
	return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',
		mt_rand(0, 0xffff), mt_rand(0, 0xffff),
		mt_rand(0, 0xffff),
		mt_rand(0, 0x0fff) | 0x4000,
		mt_rand(0, 0x3fff) | 0x8000,
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/*
 * Print Error Message And Exit
 */
function perror($err_code) {
	switch($err_code) {
	case -4:
		echo json_encode([
			'code' => -4, 
			'message' => 'Not Found'
		]);
		break;
	case -3:
		echo json_encode([
			'code' => -3, 
			'message' => 'Bad Request'
		]);
		break;
	case -2:
		echo json_encode([
			'code' => -2, 
			'message' => 'Access Denied'
		]);
		break;
	case -1:
		echo json_encode([
			'code' => -1,
			'message' => 'Service Temporarily Unavailable'
		]);
		break;
	default:
		echo json_encode([
			'code' => 0,
			'message' => 'Store.Home API v0.0.1'
		]);
		break;
	}
	ob_end_flush();
	exit;
}

/*
 * Connect to Database
 */
try {
	$dbh = new PDO('mysql:host=localhost;dbname=store;charset=utf8', 'store', 'store');
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbh->setAttribute(PDO::ATTR_AUTOCOMMIT, 0);
	$dbh->beginTransaction();
} catch (Exception $err) {
	perror(-1);
}

/*
 * Object 
 */
if(!isset($_REQUEST['object'])) {
	perror(0);
}
switch($_REQUEST['object']) {
case 'id':
	echo json_encode([
		'code' => 200,
		'message' => 'OK',
		'id' => randomUUID()
	]);
	break;
case 'category':
	switch($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		/* 
		 * @param id
		 */
		$result=[];
		try {
			$select=$dbh->prepare('SELECT * FROM `category` WHERE `id`=:id');
			$select->bindValue(':id', $_REQUEST['id']);
    		$select->execute();
    		if($select->rowCount()!=1)
    			perror(-4);
    		$row=$select->fetch();
    		$result['id']=$row['id'];
    		$result['name']=$row['name'];
    		$result['parent']=$row['parent'];
    		
    		$result['attr_name']=[];
    		$result['attr_main']=[];    		
    		$select2=$dbh->prepare('SELECT * FROM `attribute` WHERE `category`=:category');
			$select2->bindValue(':category', $_REQUEST['id']);
    		$select2->execute();
    		while($row2=$select2->fetch()) {
    			$result['attr_name'][]=$row2['name'];
    			$result['attr_main'][]=$row2['main'];
    		}
		} catch (Exception $e) {
			perror(-2);
		}
		echo json_encode([
			'code' => 200,
			'message' => 'OK',
			'data' => $result
		]);
		break;
	case 'POST':
		/* 
		 * @param id
		 * @param name
		 * @param attr_id[]
		 * @param attr_name_?[]
		 * @param attr_main_?[]
		 */
		$attrs=[];
		if(isset($_POST['category']) && strlen(trim($_POST['category'])) > 0
			&& isset($_POST['attr_id']) && count($_POST['attr_id']) > 0) {
			// get inherit attributes
			$ia_list=[];
			try {
				$ia_category_parent=$_POST['category'];
				$ia_category_id=0;
				while($ia_category_parent!=$ia_category_id) {
					$select=$dbh->prepare('SELECT * FROM `category` WHERE `id`=:id');
					$select->bindValue(':id', $ia_category_parent);
					$select->execute();
					if($row=$select->fetch()) {
						$ia_category_id=$ia_category_parent;
						$ia_category_parent=$row['parent'];
					} else {
						perror(-3);
					}
					$select=$dbh->prepare('SELECT * FROM `attribute` WHERE `category`=:category');
					$select->bindValue(':category', $ia_category_id);
					$select->execute();
					while($row=$select->fetch()) {
						$ia=[];
						$ia['category']=$row['category'];
						$ia['name']=$row['name'];
						$ia['main']=$row['main'];
						$ia_list[]=$ia;
					}
				}
			} catch (Exception $e) {
				perror(-2);
			}
			foreach($_POST['attr_id'] as $attr_id) {
				if(!isset($_POST['attr_name_'.$attr_id]))
					continue;
				$exists=0;
				foreach($ia_list as $ia) {
					if($ia['name']==trim($_POST['attr_name_'.$attr_id])) {
						$exists=1;
						break;
					}
				}
				if($exists)
					perror(-3);
				$attr=[];
				$attr['name_old']='';
				$attr['name']=trim($_POST['attr_name_'.$attr_id]);
				$attr['main']=0;
				if(isset($_POST['attr_name_old_'.$attr_id]) && strlen(trim($_POST['attr_name_old_'.$attr_id])) > 0)
					$attr['name_old']=trim($_POST['attr_name_old_'.$attr_id]);
				if(isset($_POST['attr_main_'.$attr_id]) && $_POST['attr_main_'.$attr_id]=="on")
					$attr['main']=1;
				$attrs[]=$attr;
			}
		}
		if(isset($_POST['id']) && strlen($_POST['id']) > 0) {
			if(!isset($_POST['name']))
				perror(-3);
			if(strlen($_POST['name']) > 0) {
				// modify
				if(!isset($_POST['category']) || strlen(trim($_POST['category'])) == 0)
					perror(-3);
				try {
					$update=$dbh->prepare('UPDATE `category` SET `name`=:name, `parent`=:parent WHERE `id`=:id');
					$update->bindValue(':name', $_POST['name']);
					$update->bindValue(':parent', $_POST['category']);
					$update->bindValue(':id', $_POST['id']);
					$update->execute();
					
					foreach($attrs as $attr) {
						if(strlen($attr['name']) == 0) {
							if(strlen($attr['name_old']) > 0) {
								// delete
								$delete=$dbh->prepare('DELETE FROM `attribute` WHERE `category`=:category AND `name`=:name_old');
								$delete->bindValue(':name_old', $attr['name_old']);
								$delete->bindValue(':category', $_POST['id']);
								$delete->execute();
							}
						} else {
							if(strlen($attr['name_old']) > 0) {
								// update
								$update=$dbh->prepare('UPDATE `attribute` SET `name`=:name, `main`=:main WHERE `category`=:category AND `name`=:name_old');
								$update->bindValue(':name', $attr['name']);
								$update->bindValue(':name_old', $attr['name_old']);
								$update->bindValue(':main', $attr['main']);
								$update->bindValue(':category', $_POST['id']);
								$update->execute();
							} else {
								// new
								$insert=$dbh->prepare('INSERT INTO `attribute` (`category`, `name`, `main`) VALUES (:category, :name, :main)');
								$insert->bindValue(':name', $attr['name']);
								$insert->bindValue(':main', $attr['main']);
								$insert->bindValue(':category', $_POST['id']);
								$insert->execute();
							}
						}
					}
					$dbh->commit();
					echo json_encode([
						'code' => 200,
						'message' => 'OK',
						'id' => $_POST['id']
					]);
					$ref['id']=$_POST['id'];
				} catch (Exception $e) {
					perror(-3);
				}
			} else {
				// delete
				try {
					$delete=$dbh->prepare('DELETE FROM `attribute` WHERE `category`=:category');
					$delete->bindValue(':category', $_POST['id']);
					$delete->execute();
					$delete=$dbh->prepare('DELETE FROM `category` WHERE `id`=:id');
					$delete->bindValue(':id', $_POST['id']);
					$delete->execute();
					$dbh->commit();
					echo json_encode([
						'code' => 200,
						'message' => 'OK',
						'id' => $_POST['id']
					]);
				} catch (Exception $e) {
					perror(-3);
				}
			}
		} else {
			// new
			if(!isset($_POST['name']) || strlen(trim($_POST['name'])) == 0)
				perror(-3);
			if(!isset($_POST['category']) || strlen(trim($_POST['category'])) == 0)
				perror(-3);
			try {
				$insert=$dbh->prepare('INSERT INTO `category` (`id`, `parent`, `name`) VALUES (:id, :parent, :name)');
				$insert->bindValue(':name', $_POST['name']);
				$insert->bindValue(':parent', $_POST['category']);
				$insert->bindValue(':id', $_POST['id']=randomUUID());
    			$insert->execute();
    			
    			foreach($attrs as $attr) {
					if(strlen($attr['name']) == 0) {
						if(strlen($attr['name_old']) > 0) {
							// delete
							$delete=$dbh->prepare('DELETE FROM `attribute` WHERE `category`=:category AND `name`=:name_old');
							$delete->bindValue(':name_old', $attr['name_old']);
							$delete->bindValue(':category', $_POST['id']);
							$delete->execute();
						}
					} else {
						if(strlen($attr['name_old']) > 0) {
							// update
							$update=$dbh->prepare('UPDATE `attribute` SET `name`=:name, `main`=:main WHERE `category`=:category AND `name`=:name_old');
							$update->bindValue(':name', $attr['name']);
							$update->bindValue(':name_old', $attr['name_old']);
							$update->bindValue(':main', $attr['main']);
							$update->bindValue(':category', $_POST['id']);
							$update->execute();
						} else {
							// new
							$insert=$dbh->prepare('INSERT INTO `attribute` (`category`, `name`, `main`) VALUES (:category, :name, :main)');
							$insert->bindValue(':name', $attr['name']);
							$insert->bindValue(':main', $attr['main']);
							$insert->bindValue(':category', $_POST['id']);
							$insert->execute();
						}
					}
    			}
    			$dbh->commit();
				echo json_encode([
					'code' => 200,
					'message' => 'OK',
					'id' => $_POST['id']
				]);
				$ref['id']=$_POST['id'];
			} catch (Exception $e) {
				perror(-3);
			}
		}
		break;
	default:
		perror(-3);
		break;
	}
	break;
case 'categories':
	switch($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		/* 
		 * @param [id]
		 */
		$list=[];
		if(!isset($_REQUEST['id'])) {
			// get root id
			try {
				$select=$dbh->prepare('SELECT * FROM `category` WHERE `parent`=`id`');
    			$select->execute();
    			$row=$select->fetch();
    			$_REQUEST['id']=$row['id'];
			} catch (Exception $e) {
				perror(-1);
			}
		}
		// exists?
		try {
			$select=$dbh->prepare('SELECT * FROM `category` WHERE `id`=:id');
			$select->bindValue(':id', $_REQUEST['id']);
    		$select->execute();
    		if($select->rowCount()!=1)
    			perror(-4);
    		$row=$select->fetch();
    		$category['id']=$row['id'];
    		$category['name']=$row['name'];
    		$category['parent']=$row['parent'];
    		$category['children']=[];
    		
    		$category['attr_name']=[];
    		$category['attr_main']=[];   
    		$select2=$dbh->prepare('SELECT * FROM `attribute` WHERE `category`=:category');
			$select2->bindValue(':category', $row['id']);
    		$select2->execute();
    		while($row2=$select2->fetch()) {
    			$category['attr_name'][]=$row2['name'];
    			$category['attr_main'][]=$row2['main'];
    		}
    		
    		$list[]=$category;
		} catch (Exception $e) {
			perror(-2);
		}
		// build list
		$job_list[]=&$list[0];
		while(count($job_list)>0) {
			$job_list_new=[];
			for($i=0;$i<count($job_list);$i++) {
				$job=&$job_list[$i];
				try {
					$select=$dbh->prepare('SELECT * FROM `category` WHERE `parent`=:id AND `id`!=:id');
					$select->bindValue(':id', $job['id']);
					$select->execute();
					if($select->rowCount()>0) {
						while($row=$select->fetch()) {
							$category=[];
							$category['id']=$row['id'];
							$category['name']=$row['name'];
							$category['parent']=$row['parent'];
							$category['children']=[];
							
							$category['attr_name']=[];
							$category['attr_main']=[];   
							$select2=$dbh->prepare('SELECT * FROM `attribute` WHERE `category`=:category');
							$select2->bindValue(':category', $row['id']);
							$select2->execute();
							while($row2=$select2->fetch()) {
								$category['attr_name'][]=$row2['name'];
								$category['attr_main'][]=$row2['main'];
							}
							$job['children'][]=$category;
							$job_list_new[]=&$job['children'][count($job['children'])-1];
						}
					}
				} catch (Exception $e) {
					perror(-2);
				}
			}
			$job_list=$job_list_new;
		}
		echo json_encode([
			'code' => 200,
			'message' => 'OK',
			'data' => $list
		]);
		break;
	default:
		perror(-3);
		break;
	}
	break;
case 'item':
	switch($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		/* 
		 * @param id
		 */
		$result=[];
		try {
			$select=$dbh->prepare('SELECT * FROM `item` WHERE `id`=:id');
			$select->bindValue(':id', $_REQUEST['id']);
    		$select->execute();
    		if($select->rowCount()!=1)
    			perror(-4);
    		$row=$select->fetch();
    		$result['id']=$row['id'];
    		$result['name']=$row['name'];
    		$result['count']=$row['count'];
    		$result['photo']=$row['photo'];
    		if(strlen($row['photo'])==64) {
    			$result['photo']="/image/".$row['photo'].".png";
    		}
    		
    		$result['attribute']=[];	
    		$select2=$dbh->prepare('SELECT * FROM `item_attribute` WHERE `item`=:item');
			$select2->bindValue(':item', $_REQUEST['id']);
    		$select2->execute();
    		while($row2=$select2->fetch()) {
			$attr=[];
    			$attr['name']=$row2['name'];
    			$attr['value']=$row2['value'];
    			$result['attribute'][] = $attr;
    		}
    		
    		$result['category']=[];	
    		$select3=$dbh->prepare('SELECT * FROM `item_category` WHERE `item`=:item');
			$select3->bindValue(':item', $_REQUEST['id']);
    		$select3->execute();
    		while($row3=$select3->fetch()) {
    			$result['category'][] = $row3['category'];
    		}
		} catch (Exception $e) {
			perror(-2);
		}
		echo json_encode([
			'code' => 200,
			'message' => 'OK',
			'data' => $result
		]);
		break;
	case 'POST':
		/* 
		 * @param id
		 * @param name
		 * @param attr_id[]
		 * @param attr_name_?[]
		 * @param attr_value_?[]
		 */
		$attrs=[];
		if(isset($_POST['attr_id']) && count($_POST['attr_id']) > 0) {
			foreach($_POST['attr_id'] as $attr_id) {
				if(!isset($_POST['attr_name_'.$attr_id]) || !isset($_POST['attr_value_'.$attr_id]))
					continue;
				$attr=[];
				$attr['name_old']='';
				$attr['name']=trim($_POST['attr_name_'.$attr_id]);
				$attr['value']=trim($_POST['attr_value_'.$attr_id]);
				if(isset($_POST['attr_name_old_'.$attr_id]) && strlen(trim($_POST['attr_name_old_'.$attr_id])) > 0)
					$attr['name_old']=trim($_POST['attr_name_old_'.$attr_id]);
				$attrs[]=$attr;
			}
		}
		$img_name='';
		if(isset($_POST['photo']) && strlen($_POST['photo']) > 0 && strpos($_POST['photo'], "data:image/png;base64,")===0) {
			$img_data = base64_decode(substr($_POST['photo'], strlen("data:image/png;base64,")));
			$img = imagecreatefromstring($img_data);
			$ob_cache = ob_get_clean();
			ob_start();
			imagepng($img);
			$img_data = ob_get_clean();
			ob_start();
			echo $ob_cache;
			imagedestroy($img);
			$img_name=hash("sha256", $img_data, false);
			file_put_contents($_SERVER["DOCUMENT_ROOT"]."/image/".$img_name.".png", $img_data);
		}
		if(isset($_POST['id']) && strlen($_POST['id']) > 0) {
			if(!isset($_POST['name']) || !isset($_POST['count']))
				perror(-3);
			if(strlen($_POST['name']) > 0) {
				// modify
				if(!isset($_POST['category']) || count($_POST['category']) == 0)
					perror(-3);
				try {
					$update=$dbh->prepare('UPDATE `item` SET `name`=:name, `count`=:count WHERE `id`=:id');
					$update->bindValue(':name', $_POST['name']);
					$update->bindValue(':count', $_POST['count']);
					$update->bindValue(':id', $_POST['id']);
					$update->execute();
					
					if(strlen($img_name)==64) {
						$update=$dbh->prepare('UPDATE `item` SET `photo`=:photo WHERE `id`=:id');
						$update->bindValue(':photo', $img_name);
						$update->bindValue(':id', $_POST['id']);
						$update->execute();
					}
					
					$delete=$dbh->prepare('DELETE FROM `item_category` WHERE `item`=:item');
					$delete->bindValue(':item', $_POST['id']);
					$delete->execute();
					foreach($_POST['category'] as $category_id) {
						$insert=$dbh->prepare('INSERT INTO `item_category` (`item`, `category`) VALUES (:item, :category)');
						$insert->bindValue(':item', $_POST['id']);
						$insert->bindValue(':category', $category_id);
						$insert->execute();
					}
					
					foreach($attrs as $attr) {
						if(strlen($attr['name']) == 0 || strlen($attr['value']) == 0) {
							if(strlen($attr['name_old']) > 0) {
								// delete
								$delete=$dbh->prepare('DELETE FROM `item_attribute` WHERE `item`=:item AND `name`=:name_old');
								$delete->bindValue(':name_old', $attr['name_old']);
								$delete->bindValue(':item', $_POST['id']);
								$delete->execute();
							}
						} else {
							if(strlen($attr['name_old']) > 0) {
								// update
								$update=$dbh->prepare('UPDATE `item_attribute` SET `name`=:name, `value`=:value WHERE `item`=:item AND `name`=:name_old');
								$update->bindValue(':name', $attr['name']);
								$update->bindValue(':name_old', $attr['name_old']);
								$update->bindValue(':value', $attr['value']);
								$update->bindValue(':item', $_POST['id']);
								$update->execute();
							} else {
								// new
								$insert=$dbh->prepare('INSERT INTO `item_attribute` (`item`, `name`, `value`) VALUES (:item, :name, :value)');
								$insert->bindValue(':name', $attr['name']);
								$insert->bindValue(':value', $attr['value']);
								$insert->bindValue(':item', $_POST['id']);
								$insert->execute();
							}
						}
					}
					$dbh->commit();
					echo json_encode([
						'code' => 200,
						'message' => 'OK',
						'id' => $_POST['id']
					]);
					$ref['id']=$_POST['id'];
				} catch (Exception $e) {
					perror(-3);
				}
			} else {
				// delete
				try {
					$delete=$dbh->prepare('DELETE FROM `item_attribute` WHERE `item`=:item');
					$delete->bindValue(':item', $_POST['id']);
					$delete->execute();
					$delete=$dbh->prepare('DELETE FROM `item_category` WHERE `item`=:item');
					$delete->bindValue(':item', $_POST['id']);
					$delete->execute();
					$delete=$dbh->prepare('DELETE FROM `item` WHERE `id`=:item');
					$delete->bindValue(':item', $_POST['id']);
					$delete->execute();
					$dbh->commit();
					echo json_encode([
						'code' => 200,
						'message' => 'OK',
						'id' => $_POST['id']
					]);
				} catch (Exception $e) {
					var_dump($e);
					perror(-3);
				}
			}
		} else {
			// new
			if(!isset($_POST['name']) || strlen(trim($_POST['name'])) == 0)
				perror(-3);
			if(!isset($_POST['count']))
				perror(-3);
			if(!isset($_POST['category']) || count($_POST['category']) == 0)
				perror(-3);
			if(!isset($_POST['photo']))
				$_POST['photo']='';
			try {
				$insert=$dbh->prepare('INSERT INTO `item` (`id`, `name`, `count`, `photo`) VALUES (:id, :name, :count, :photo)');
				$insert->bindValue(':name', $_POST['name']);
				$insert->bindValue(':count', $_POST['count']);
				$insert->bindValue(':photo', '');
				$insert->bindValue(':id', $_POST['id']=randomUUID());
    			$insert->execute();
    			
    			if(strlen($img_name)==64) {
					$update=$dbh->prepare('UPDATE `item` SET `photo`=:photo WHERE `id`=:id');
					$update->bindValue(':photo', $img_name);
					$update->bindValue(':id', $_POST['id']);
					$update->execute();
				}
    			foreach($_POST['category'] as $category_id) {
					$insert=$dbh->prepare('INSERT INTO `item_category` (`item`, `category`) VALUES (:item, :category)');
					$insert->bindValue(':item', $_POST['id']);
					$insert->bindValue(':category', $category_id);
					$insert->execute();
    			}
    			
    			foreach($attrs as $attr) {
					if(strlen($attr['name']) == 0 || strlen($attr['value']) == 0) {
						if(strlen($attr['name_old']) > 0) {
							// delete
							$delete=$dbh->prepare('DELETE FROM `item_attribute` WHERE `item`=:item AND `name`=:name_old');
							$delete->bindValue(':name_old', $attr['name_old']);
							$delete->bindValue(':item', $_POST['id']);
							$delete->execute();
						}
					} else {
						if(strlen($attr['name_old']) > 0) {
							// update
							$update=$dbh->prepare('UPDATE `item_attribute` SET `name`=:name, `value`=:value WHERE `item`=:item AND `name`=:name_old');
							$update->bindValue(':name', $attr['name']);
							$update->bindValue(':name_old', $attr['name_old']);
							$update->bindValue(':value', $attr['value']);
							$update->bindValue(':item', $_POST['id']);
							$update->execute();
						} else {
							// new
							$insert=$dbh->prepare('INSERT INTO `item_attribute` (`item`, `name`, `value`) VALUES (:item, :name, :value)');
							$insert->bindValue(':name', $attr['name']);
							$insert->bindValue(':value', $attr['value']);
							$insert->bindValue(':item', $_POST['id']);
							$insert->execute();
						}
					}
    			}
    			$dbh->commit();
				echo json_encode([
					'code' => 200,
					'message' => 'OK',
					'id' => $_POST['id']
				]);
				$ref['id']=$_POST['id'];
			} catch (Exception $e) {
				perror(-3);
			}
    			
		}
		break;
	default:
		perror(-3);
		break;
	}
	break;
case 'items':
	switch($_SERVER['REQUEST_METHOD']) {
	case 'GET':
		/* 
		 * @param category
		 * @param words
		 */
		if(!isset($_REQUEST['category']) || count($_REQUEST['category']) == 0)
			perror(-3);
		$words=[];
		if(isset($_REQUEST['words'])) {
			$tmp=explode(" ", $_REQUEST['words']);
			for($i=0;$i<count($tmp);$i++)
				if(strlen(trim($tmp[$i]))>0)
					$words[]=trim($tmp[$i]);
		}
		
		
		$sql='SELECT DISTINCT `item`.`id` `id`, `item`.`name` `name`, `item`.`count` `count`, `item`.`photo` `photo` FROM `item` LEFT JOIN `item_category` ON `item_category`.`item`=`item`.`id` WHERE ( `item_category`.`category`=:category';
		for($i=1;$i<count($_REQUEST['category']);$i++) {
			$sql.=" OR `item_category`.`category`=:category_".$i;
		}
		if(count($words) > 0) {
			$sql.=') AND (`item`.`name` like :name';
			for($i=1;$i<count($words);$i++) {
				$sql.=" OR `item`.`name` like :name_".$i;
			}
		}
		$sql.=')';
		$list=[];
		try {
			$select=$dbh->prepare($sql);
			$select->bindValue(':category', $_REQUEST['category'][0]);
			for($i=1;$i<count($_REQUEST['category']);$i++) {
				$select->bindValue(':category_'.$i, $_REQUEST['category'][$i]);
			}
			if(count($words) > 0) {
				$select->bindValue(':name', "%".$words[0]."%");
				for($i=1;$i<count($words);$i++) {
					$select->bindValue(':name_'.$i, "%".$words[$i]."%");
				}
			}
    		$select->execute();
    		$items=[];
    		while($row=$select->fetch()) {
			$item=[];
    			$item['id']=$row['id'];
    			$item['name']=$row['name'];
    			$item['count']=$row['count'];
			if(strlen($row['photo'])==64) {
    				$item['photo']="/image/".$row['photo'].".png";
    			}
    			$item['attribute']=[];
    			$select2=$dbh->prepare('SELECT * FROM `item_attribute` WHERE `item`=:item');
				$select2->bindValue(':item', $row['id']);
				$select2->execute();
				while($row2=$select2->fetch()) {
					$attr=[];
					$attr['name']=$row2['name'];
					$attr['value']=$row2['value'];
					$item['attribute'][]=$attr;
				}
    			$items[]=$item;
    		}
			echo json_encode([
				'code' => 200,
				'message' => 'OK',
				'data' => $items
			]);
		} catch (Exception $e) {
		echo $e;
			perror(-1);
		}
		break;
	default:
		perror(-3);
		break;
	}
	break;
default:
	perror(-3);
	break;
}

if(isset($_REQUEST['ref'])) {
	$_REQUEST['ref']=str_replace("{id}",$ref['id'],$_REQUEST['ref']);
	header("Location: ".$_REQUEST['ref']);
}
ob_end_flush();

?>
