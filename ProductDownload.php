<?php

/*
  _   _       _______ _    _          _   _   _    _ ______ _____ _   _ _____  _____ _____ _    _ 
 | \ | |   /\|__   __| |  | |   /\   | \ | | | |  | |  ____|_   _| \ | |  __ \|_   _/ ____| |  | |
 |  \| |  /  \  | |  | |__| |  /  \  |  \| | | |__| | |__    | | |  \| | |__) | | || |    | |__| |
 | . ` | / /\ \ | |  |  __  | / /\ \ | . ` | |  __  |  __|   | | | . ` |  _  /  | || |    |  __  |
 | |\  |/ ____ \| |  | |  | |/ ____ \| |\  | | |  | | |____ _| |_| |\  | | \ \ _| || |____| |  | |
 |_| \_/_/    \_\_|  |_|  |_/_/    \_\_| \_| |_|  |_|______|_____|_| \_|_|  \_\_____\_____|_|  |_|
                                                                                                  
Products folder generator by Nathan Heinrich 2016
Used to create folder sorted versions of wordpress products from WooCommerce
*/                                                                                                  
                                                                                                  
//Database, leave plank if local
define(ENDPOINT, "");
//Database username
define(USERNAME, "pmkevinrudd");
//Database password
define(PASSWORD, "");
//Database name
define(DATABASE, "atc");
//Remote url to get product images from
define(WEB_URL, "http://www.allthingscontainers.com.au/wp-content/uploads/");
//Where to save images and descriptions locally
define(SAVE_FOLDER, "Products");

class ProductDownload{

    public $GetAllProductsSQL = "SELECT * FROM `atc_posts` WHERE `post_type` = 'product' AND `post_status` =  'publish'";
    public $GetAllProductsMetadataSQL = "SELECT * FROM `atc_postmeta` WHERE `post_id` = ";

    public function __construct()
    {
        $this->GetProducts();
    }
    
    public function select($sql)
    {
        $conn = new mysqli(ENDPOINT, USERNAME, PASSWORD, DATABASE);
        if(!$conn)
        {
            if($conn->MysqlConnection->connect_error) {
                die("Error connecting to DB");
            } else {
                echo "connected";
            }                
        }

        $result = $conn->query($sql);
        $list = array();
        if ($result->num_rows > 0) {
    		// output data of each row
        	while($row = $result->fetch_assoc()) {
        		$list[] = $row;
        	}	
        }else{
            echo "";
        }
        return $list;
 
    }
    
    private function GetProducts()
    {
        $Products[] = $this->select($this->GetAllProductsSQL);
        
        foreach($Products[0] as &$Product)
        {
            //Make sure the filename doesnt contain any characters that could cause problems with folder saving
            $ProductFolderName = str_replace('/', '', $Product['post_title']);
            
            $Folder = SAVE_FOLDER . "/" . $ProductFolderName . "/";

            //The folder already exists
            if (file_exists($Folder))
            {
                continue;
            }
            //Save Products
            if(!mkdir($Folder, 0777, true))
            {
                die;
            }
            //Description file already exists
            if(file_exists($Folder . "//Description.txt"))
            {
                continue;
            }
            //Write the description file
            file_put_contents($Folder . "//Description.txt", $Product['post_content']);
            
            $this->GetProductMeta($Product['ID'], $Folder);
        }
    } 
    
    private function GetProductMeta($ProductId, $Folder)
    {
        
        $ProductMetaDataSQL = $this->GetAllProductsMetadataSQL . $ProductId;
        $ProductsMetaData[] = $this->select($ProductMetaDataSQL);
        
        foreach($ProductsMetaData[0] as $MetaData)
        {
            //If the metadata is either the thumbnail image link or image gallery link send the post id to download
            if($MetaData['meta_key'] == "_thumbnail_id" || $MetaData['meta_key'] == "_product_image_gallery")
            {
                //Make sure the post id isnt blank
                if($MetaData['meta_value'] != "")
                {
                    $this->GetProductImages($MetaData['meta_value'], $Folder);
                }
            }
        }
        echo "</br>";
    }
    
    private function GetProductImages($MetaId, $Folder)
    {
        $ProductMetaDataSQL = $this->GetAllProductsMetadataSQL . $MetaId;
        $ProductImages[] = $this->select($ProductMetaDataSQL);
        foreach($ProductImages[0] as $Images)
        {
            //make sure the metadata is the image link
            if($Images['meta_key'] == "_wp_attached_file")
            {
                //Download the image
                $this->DownloadImage(WEB_URL . $Images['meta_value'], $Folder, $Images['meta_value']);
            }
        }

    }
    
    private function DownloadImage($Url, $Folder, $Filename)
    {
        
        $ExplodedFilename = explode("/", $Filename);

        //Check if the file already exists to speed the script up a bit
        if(file_exists($Folder . end($ExplodedFilename)))
            return;
            
        //If the image doesnt currently exist download it from the remote server
        echo "Copy From: ". $Url . " | To: " . $Folder . end($ExplodedFilename);
        copy($Url, $Folder . end($ExplodedFilename));
    }

}

$productinfo = new ProductDownload();