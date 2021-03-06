<?php
require_once('Data_Holder.php');
require_once('classes/PriceHolder.php');
require_once('classes/CategoryHolder.php');

class ProductHolder extends Data_Holder 
{
	function ProductHolder($id = null)
	{
		$table = 'product';
		$key_info = array('key_name' => 'id');
		if (!is_null($id)) $key_info['key_value'] = $id;
		$this->init($table,$key_info);
	}
	
	function getAll()
	{
		$result = $GLOBALS['core.sql']->getAll("select * from #p#".$this->table." order by order_no ",array(),$this->key_info['key_name']);
		return $result;
	}

        function getAllbyFragrance($fragrance_id)
	{
		$result = $GLOBALS['core.sql']->getAll("select * from #p#".$this->table." WHERE fragrance_id=? order by order_no ",array($fragrance_id),$this->key_info['key_name']);
		return $result;
	}
	
	function getAllActive()
	{
		$result = $GLOBALS['core.sql']->getAll("select * from #p#".$this->table." where inactive = 0 order by order_no ",array(),$this->key_info['key_name']);
		return $result;
	}	
	
	function getAllOptions()
	{
		$result = $GLOBALS['core.sql']->getAll("select * from #p#product_option WHERE inactive <> 1 order by order_no ",array(),'id');
		return $result;
	}
    
	function getAllOptionsAdm()
	{
		$result = $GLOBALS['core.sql']->getAll("select * from #p#product_option order by order_no ",array(),'id');
		return $result;
	}
	
	function getFeatured($inactive = 0)
	{
		$result = $GLOBALS['core.sql']->getAll("select * from #p#".$this->table." where inactive = ? and featured = ? order by order_no ",array($inactive,1),$this->key_info['key_name']);
		return $result;
	}
	
	function getForFeaturedSection($count,$random)
	{
		$order = " order by id ";
		if ($random) $order = " order by rand() ";
		return $GLOBALS['core.sql']->getAll("select * from #p#".$this->table." where featured = ? AND inactive = ?".$order." limit ".$count,array(1,0),'id');
	}
	
	function loadBySeo($seo)
	{
		$id = $GLOBALS['core.sql']->getOne("select id from #p#" . $this->table . " where seo = ?",array($seo));		
		if (!empty($id)) 
		{
			$this->load($id);
			return true;		
		}
		return false;
	}
	
	function get_extremums()
	{
		$id = $this->get_key_value();
		$extremums = array();
		if (!empty($id))
		{
			$price_id = $this->get_data('price_category');
			$ph = new PriceHolder();
			$extremums = $ph->get_min_max($price_id);
		}
		return $extremums;
	}
	
	function getByCategory($category_id,$inactive = false, $brand_id = false)
	{
		$ch = new CategoryHolder();
		$products = $ch->getProductsForCategory($category_id);
		$products = array_keys($products);		
		$where = " 1=1 ";
		if ($inactive)
		{
			$mods[] = 0;
			$where.= " and inactive = ? ";
		}
		
		if ($brand_id)
		{
			$mods[] = $brand_id;
			$where .= " and brand_id = ? ";
		}
		
		if (!empty($products))
			$result = $GLOBALS['core.sql']->getAll("select * from #p#".$this->table." where id in (" . join(",", $products) . ") and ".$where." order by order_no ",$mods,$this->key_info['key_name']);
		else $result = array();
		return $result;
	}
	
	function getIdsByCategories($category_ids)
	{
		$join = join(",",$category_ids);
		$ids = $GLOBALS['core.sql']->getAll("select id from #p#".$this->table." where category_id in (".$join.") ",array(),'id');
		if (!empty($ids)) return array_keys($ids);
		else return array();
	}
	
	function get_data($field_name = '')
	{
		$data = parent::get_data($field_name);
		if (empty($field_name))
		{
			//for viewing data
			$id = $this->get_key_value();

			if($GLOBALS['core.auth.user']->is_member_of('admin'))
            {
                $data['options'] = $this->getOptionsAdm();
            }
            else
            {
                $data['options'] = $this->getOptions();
            }
            
            $ch = new CategoryHolder();
            $data['category_ids'] = $ch->getCategoriesForProduct($this->get_key_value());
		}
		elseif ($field_name == 'price')
		{
			//for calculations
			$id = $this->get_key_value();
			if (!empty($id))
			{
				$sh = new SpecialHolder();
				$special = $sh->getSpecialForProduct($id,$data);
				if (!empty($special))	$data = $special['new_price'];
			}
		}
		return $data;
	}
	
	function get_simple_data($field_name = '')
	{
		$data = parent::get_data($field_name);
		return $data;
	}
	
	function calculate_price($width,$height)
	{
		$to_ret = -1;
		$id = $this->get_key_value();
		if (!empty($id))
		{
			$price_id = $this->get_data('price_category');
			$ph = new PriceHolder();
			$list = $ph->getByCategory($price_id);
			
			$min_width = 1000000;
			$min_height = 1000000;
			$min_width_id = 0;
			$min_height_id = 0;
			$res_id = 0;
			$min_square = 1000000;
			$square = 0;
			foreach ($list as $key => $value)
			{
				if ($value['width'] == $width && $value['height'] == $height) 
				{
					$res_id = $key;
					break;
				}
				
				if (($value['width'] - $width) >=0 && ($value['height'] - $height) >= 0)
				{
					$square = $value['width']*$value['height'];
					if ($square < $min_square) 
					{
						$min_square = $square;
						$res_id = $key;
					}
				}
			}
			
			if (!empty($res_id))
			{
				$to_ret = $list[$res_id]['price'];
			}
			else $to_ret = -1;
		}
		
		return $to_ret;
	}
	
	function load_lists()
	{
		require_once('classes/CategoryHolder.php');
		$ch = new CategoryHolder();
		/*
		$list = $ch->getAll();
		$to_ret = array();
		foreach ($list as $key => $value)
		{
			if (isset($value['parent_id']) && !empty($value['parent_id']))			
				$to_ret[$key] = $list[$value['parent_id']]['title'].' / '.$value['title'];
			elseif (isset($value['parent_id'])) $to_ret[$key] = $value['title'];
			
		}
		*/
		$to_ret = $ch->getCategoriesForSelect();
		
		$GLOBALS['core.smarty']->assign('container_categories',$to_ret);
		
		require_once('classes/DropdownManager.php');
		$dh = new DropdownManager();
		$dh->load_lists();	
	}
	
	function isAccessable()
	{
		$id = $this->get_key_value();
		if (!empty($id) && $this->get_data('active') == 0) return true;
		return false;
	}
	
	function hasActiveCategory()
	{		
		$data = $this->get_data();		
		$ch = new CategoryHolder();
		$active_categories = $ch->getAll();						
		$has_active = false;
		//pp($data);
		//pp($active_categories);
		foreach ($data['category_ids'] as $key => $value)		
			if (isset($active_categories[$key])) $has_active = true;				
		return $has_active;	
	}
	
	function addToWishlist($user_id)
	{
		$id = $this->get_key_value();
		if (!empty($id))
		{
			$wh = new WishlistHelper();
			return $wh->addToWishlist($id,$user_id);
		}
		return 'Product is not initialized';
	}
	
	function removeFromWishlist($user_id)
	{
		$id = $this->get_key_value();
		if (!empty($id))
		{
			$wh = new WishlistHelper();
			return $wh->removeFromWishlist($id,$user_id);
		}
		return 'Product is not initialized';
	}
	
	function getOptions()
	{
		$options = array();
		$id = $this->get_key_value();
		if (!empty($id))	$options = $GLOBALS['core.sql']->getAll("select * from #p#product_option where product_id = ? AND inactive <> 1 order by order_no ",array($id),'id');
		return $options;
	}

	function getOptionsAdm()
	{
		$options = array();
		$id = $this->get_key_value();
		if (!empty($id))	$options = $GLOBALS['core.sql']->getAll("select * from #p#product_option where product_id = ? order by order_no ",array($id),'id');
		return $options;
	}
	
	function saveOption($option,$id)
	{
		$option['product_id'] = $this->get_key_value();
		$GLOBALS['core.store']->save('product_option',$option,array('key_name' => 'id','key_value' => $id));
	}
	
	function addOption($option)
	{
		$option['product_id'] = $this->get_key_value();
		$GLOBALS['core.store']->save('product_option',$option,array('key_name' => 'id'));
	}
	
	function deleteOption($id)
	{
		$GLOBALS['core.store']->delete('product_option',array('key_name' => 'id','key_value' => $id));
	}
	
	function copy()
	{
		$id = $this->get_key_value();
		if (!empty($id))
		{
			$data = $this->get_simple_data();
			if (isset($data['picture']) && !empty($data['picture']))
			{
				$path = './pictures/';
				$filename = $this->upload_file($data['picture'], $path);
				if (file_exists($path . $data['picture'])) copy($path . $data['picture'], $path . $filename);
				if (file_exists($path . '180_' . $data['picture'])) copy($path . '180_' . $data['picture'], $path . '180_' . $filename);
				if (file_exists($path . 'full_' . $data['picture'])) copy($path . 'full_' . $data['picture'], $path . 'full_' . $filename);
				if (file_exists($path . 'large_' . $data['picture'])) copy($path . 'large_' . $data['picture'], $path . 'large_' . $filename);
				$data['picture'] = $filename;
			}
			
			$data['inactive'] = 1;
			if (isset($data['id'])) unset($data['id']);
			$new_product = new ProductHolder();
			$new_product->set_data($data);
			$new_product->save();
			
			$full_data = $this->get_data();
			$ch = new CategoryHolder();
			$ch->setCategoriesForProduct($new_product->get_key_value(), $full_data['category_ids']);
			
			$new_id = $new_product->get_key_value();
			
			$options = array();
			$options = $this->getOptionsAdm();
			foreach ($options as $key => $value)
			{
				$oh = new OptionHolder();
				$value['product_id'] = $new_id;
				if (isset($value['id'])) unset($value['id']);
				$oh->set_data($value);
				$oh->save();
			}
			
			$sh = new SpecialHolder();
			$sh->loadByProductId($id);
			$sdata = $sh->get_data();
			if (!empty($sdata))
			{
				$new_special = new SpecialHolder();
				$sdata['object_id'] = $new_id;
				if (isset($sdata['id'])) unset($sdata['id']);
				$new_special->set_data($sdata);
				$new_special->save();
			}
		}
	}
	
	function loadAllProducts()
	{
		$products = $this->getAll();
		$options = $this->getAllOptions();
		$GLOBALS['core.smarty']->assign('options',$options);
		
		foreach ($products as $key => $value)
		{
			if ($value['inactive'] == 1) {unset($products[$key]);continue;}
			if (isset($specials[$key])) $products[$key] = $specials[$key];
		}
		$GLOBALS['core.smarty']->assign('products', $products);
	}
	
	function upload_file($file, $filedirectory)
 	{
  		if(!is_null($filedirectory) && is_dir($filedirectory))
  		{
   			$filedirectory = trim($filedirectory, "/");
   			$pathfile = pathinfo($file);
   			$j = 1;
   			while(file_exists($filedirectory.'/'.$file))
   			{
    			$file = $pathfile['filename'].'('.$j.').'.$pathfile['extension'];
    			$j++;
   			}
   			return $file;
  		}
  		else 
  		{
   			exit('dirrectory error');
  		}
 	}

        function get_Tops($top_string)
	{
		$result = $GLOBALS['core.sql']->getAll("SELECT * FROM #p#".$this->table." WHERE id IN($top_string)",array());
		return $result;
	}
	
	function checkStockLimit()
	{
		$sh = new SettingsHolder();
		$settings = $sh->getSettings();
		
		if (isset($settings['low_stock_limit']) 
			&& isset($settings['low_stock_notify']) && !empty($settings['low_stock_notify'])
			&& isset($settings['low_stock_email']) && !empty($settings['low_stock_email']))
			{
		
				$id = $this->get_key_value();
				if (!empty($id))
				{
					$send_email = 0;
					$data = $this->get_data();
					if ($data['has_options'] == 0)
					{
						if ($data['quantity'] <= $settings['low_stock_limit'])
						{
							$send_email = 1;
						}
					}
					else
					{
						if (isset($data['options']))
						{
							foreach ($data['options'] as $value)
							{
								if ($value['quantity'] <= $settings['low_stock_limit'])
								{
									$send_email = 1;
								}
							}
						}
					}
					
					if ($send_email)
					{
						$GLOBALS['core.application']->init_module('mailer_module',true);
						$GLOBALS['core.mail']->init();
						$GLOBALS['core.mail']->addAddress($settings['low_stock_email']);
						$GLOBALS['core.mail']->setFromName('Automatic notification');
						$GLOBALS['core.mail']->setSubject('Low stock notification');
						$data['id'] = $id;
						$GLOBALS['core.smarty']->assign('product_data', $data);
						$email = $GLOBALS['core.smarty']->fetch('Emails/low_stock_notification.tpl');
						$GLOBALS['core.mail']->setBody($email);
						$GLOBALS['core.mail']->isHTML(true);
						$GLOBALS['core.mail']->send();					
					}
				}
			}
	}

        function searchProducts($searchvalue)
        {
                $result = array();
                if (strlen($searchvalue)>0) {
                    $searchvalue = strtolower(substr($searchvalue, 0, 1));
                    $result = $GLOBALS['core.sql']->getAll("SELECT * FROM #p#".$this->table." WHERE LOWER(name) LIKE ? ORDER BY order_no ",array($searchvalue.'%'),$this->key_info['key_name']);
                }

		return $result;
        }

        function getDiscounts(){
            $result = $GLOBALS['core.sql']->getAll("SELECT * FROM #p#".$this->table." WHERE newprice > 0 ORDER BY order_no ",array(),$this->key_info['key_name']);
            return $result;
        }

        function getForDiscountSection($count,$random)
	{
		$order = " order by id ";
		if ($random) $order = " order by rand() ";
		return $GLOBALS['core.sql']->getAll("select * from #p#".$this->table." where newprice > 0 AND inactive = ?".$order." limit ".$count,array(0));
	}
}
?>