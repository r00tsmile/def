<?php

    require_once("Rest.inc.php");

    error_reporting(0);

    class API extends REST {

        public $data = "";
        const demo_version = false;

        private $db     = NULL;
        private $mysqli = NULL;
        public function __construct() {
            // Init parent contructor
            parent::__construct();
            // Initiate Database connection
            $this->dbConnect(); 
        }

        /*
         *  Connect to Database
        */
        private function dbConnect() {
            require_once ("../../includes/config.php");
            $this->mysqli = new mysqli($host, $user, $pass, $database);
            $this->mysqli->query('SET CHARACTER SET utf8');
        }

        public function processApi() {
            if(isset($_REQUEST['x']) && $_REQUEST['x']!=""){
                $func = strtolower(trim(str_replace("/","", $_REQUEST['x'])));
                if((int)method_exists($this,$func) > 0) {
                    $this->$func();
                } else {
                    header( 'Content-Type: application/json; charset=utf-8' );
                    echo 'processApi - method not exist';
                    exit;
                }
            } else {
                header( 'Content-Type: application/json; charset=utf-8' );
                echo 'processApi - method not exist';
                exit;
            }
        }       

        /* Api Checker */
        public function check_connection() {
            if (mysqli_ping($this->mysqli)) {
                $respon = array(
                    'status' => 'ok', 'database' => 'connected'
                );
                $this->response($this->json($respon), 200);
            } else {
                $respon = array(
                    'status' => 'failed', 'database' => 'not connected'
                );
                $this->response($this->json($respon), 404);
            }
        }

        public function get_wallpapers() {

            if($this->get_request_method() != "GET") $this->response('',406);
            $limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
            $page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

            $order = $_GET['order'];
            $filter = $_GET['filter'];

            $offset = ($page * $limit) - $limit;
            $count_total = $this->get_count_result("SELECT COUNT(DISTINCT g.id) FROM tbl_gallery g WHERE $filter $order");

            $query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = g.cat_id AND $filter $order LIMIT $limit OFFSET $offset";

            $post = $this->get_list_result($query);
            $count = count($post);
            $respon = array(
                'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
            );
            $this->response($this->json($respon), 200);

        }

        public function get_one_wallpaper() {

            if($this->get_request_method() != "GET") $this->response('',406);
            $id = $_GET['id'];
            $query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = g.cat_id AND g.id = $id";

            $wallpaper = $this->get_one_result($query);

            $respon = array(
                'status' => 'ok', 'wallpaper' => $wallpaper
            );
            $this->response($this->json($respon), 200);

        }

        public function get_categories() {

            include ("../../includes/config.php");

            if($this->get_request_method() != "GET") $this->response('',406);
            $setting_qry = "SELECT * FROM tbl_settings where id = '1'";
            $result = mysqli_query($connect, $setting_qry);
            $row    = mysqli_fetch_assoc($result);
            $sort   = $row['category_sort'];
            $order  = $row['category_order'];

            $query = "SELECT DISTINCT c.cid AS 'category_id', c.category_name, c.category_image, COUNT(DISTINCT g.id) as total_wallpaper
            FROM tbl_category c LEFT JOIN tbl_gallery g ON c.cid = g.cat_id GROUP BY c.cid ORDER BY $sort $order";

            $categories = $this->get_list_result($query);
            
            $count = count($categories);
            $respon = array(
                'status' => 'ok', 'count' => $count, 'categories' => $categories
            );
            $this->response($this->json($respon), 200);

        }       

        public function get_category_details() {

            if($this->get_request_method() != "GET") $this->response('',406);
            $limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
            $page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

            $id = $_GET['id'];
            $order = $_GET['order'];
            $filter = $_GET['filter'];

            $offset = ($page * $limit) - $limit;
            $count_total = $this->get_count_result("SELECT COUNT(DISTINCT g.id) FROM tbl_gallery g WHERE g.cat_id = $id AND $filter $order");

            $query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = $id AND c.cid = g.cat_id AND $filter $order LIMIT $limit OFFSET $offset";

            $post = $this->get_list_result($query);
            $count = count($post);
            $respon = array(
                'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
            );
            $this->response($this->json($respon), 200);

        }

        public function get_search() {

            if($this->get_request_method() != "GET") $this->response('',406);
            $limit = isset($this->_request['count']) ? ((int)$this->_request['count']) : 10;
            $page = isset($this->_request['page']) ? ((int)$this->_request['page']) : 1;

            $search = $_GET['search'];
            $order = $_GET['order'];

            $offset = ($page * $limit) - $limit;
            $count_total = $this->get_count_result("SELECT COUNT(DISTINCT g.id) FROM tbl_gallery g, tbl_category c WHERE c.cid = g.cat_id AND (g.image_name LIKE '%$search%' OR g.tags LIKE '%$search%')");

            $query = "SELECT g.id AS 'image_id', g.image_name, g.image AS 'image_upload', g.image_url, g.type, g.image_resolution AS 'resolution', g.image_size AS 'size', g.image_extension AS 'mime', g.view_count AS 'views', g.download_count AS 'downloads', g.featured, g.tags, c.cid AS 'category_id', c.category_name, g.last_update FROM tbl_category c, tbl_gallery g WHERE c.cid = g.cat_id AND (g.image_name LIKE '%$search%' OR g.tags LIKE '%$search%') $order LIMIT $limit OFFSET $offset";

            $post = $this->get_list_result($query);
            $count = count($post);
            $respon = array(
                'status' => 'ok', 'count' => $count, 'count_total' => $count_total, 'pages' => $page, 'posts' => $post
            );
            $this->response($this->json($respon), 200);

        }

        public function get_search_category() {

            include ("../../includes/config.php");

            if($this->get_request_method() != "GET") $this->response('',406);

            $search = $_GET['search'];

            $query = "SELECT DISTINCT c.cid AS 'category_id', c.category_name, c.category_image, COUNT(DISTINCT g.id) as total_wallpaper
            FROM tbl_category c LEFT JOIN tbl_gallery g ON c.cid = g.cat_id WHERE c.category_name LIKE '%$search%' GROUP BY c.cid ORDER BY c.cid DESC";

            $post = $this->get_list_result($query);
            $count = count($post);
            $respon = array(
                'status' => 'ok', 'count' => $count, 'categories' => $post
            );
            $this->response($this->json($respon), 200);         
        }

        public function update_view() {

            include ("../../includes/config.php");

            $image_id = $_POST['image_id'];

            $sql = "UPDATE tbl_gallery SET view_count = view_count + 1 WHERE id = '$image_id'";
            
            if (mysqli_query($connect, $sql)) {
                header( 'Content-Type: application/json; charset=utf-8' );
                echo json_encode(array('response' => "View updated"));
            } else {
                header( 'Content-Type: application/json; charset=utf-8' );
                echo json_encode(array('response' => "Failed"));
            }
            mysqli_close($connect);

        }
        
        public function update_download() {

            include ("../../includes/config.php");

            $image_id = $_POST['image_id'];

            $sql = "UPDATE tbl_gallery SET download_count = download_count + 1 WHERE id = '$image_id'";
            
            if (mysqli_query($connect, $sql)) {
                header( 'Content-Type: application/json; charset=utf-8' );
                echo json_encode(array('response' => "Download updated"));
            } else {
                header( 'Content-Type: application/json; charset=utf-8' );
                echo json_encode(array('response' => "Failed"));
            }
            mysqli_close($connect);

        }

        public function get_ads() {
            if($this->get_request_method() != "GET") $this->response('',406);

            $sql_ads = "SELECT * FROM tbl_ads";
            $sql_ads_status = "SELECT * FROM tbl_ads_status";

            $ads = $this->get_one_result($sql_ads);
            $ads_status = $this->get_one_result($sql_ads_status);

            $respon = array(
                'status' => 'ok', 'ads' => $ads, 'ads_status' => $ads_status
            );
            $this->response($this->json($respon), 200); 
        }

        public function get_settings() {
            if($this->get_request_method() != "GET") $this->response('',406);

            $package_name = $_GET['package_name'];

            $sql_settings = "SELECT * FROM tbl_settings";
            $sql_ads = "SELECT * FROM tbl_ads";
            $sql_ads_status = "SELECT * FROM tbl_ads_status";
            $sql_app = "SELECT package_name, status, redirect_url FROM tbl_app_config WHERE package_name = '$package_name' LIMIT 1";

            $settings = $this->get_one_result($sql_settings);
            $ads = $this->get_one_result($sql_ads);
            $ads_status = $this->get_one_result($sql_ads_status);

            $app_count = count($this->get_list_result($sql_app));
            $app = $this->get_one_result($sql_app);

            $app_array = array( 'package_name' => '', 'status' => '', 'redirect_url' => '');

            if ($app_count > 0) {
                $respon = array('status' => 'ok', 'app' => $app, 'settings' => $settings, 'ads' => $ads, 'ads_status' => $ads_status);
            } else {
                $respon = array('status' => 'ok', 'app' => $app_array, 'settings' => $settings, 'ads' => $ads, 'ads_status' => $ads_status);
            }

            
            $this->response($this->json($respon), 200); 
        }

         /*
         * ======================================================================================================
         * =============================== API utilities # DO NOT EDIT ==========================================
         */

        private function get_list($query) {
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) {
                $result = array();
                while($row = $r->fetch_assoc()) {
                    $result[] = $row;
                }
                $this->response($this->json($result), 200); // send user details
            }
            $this->response('',204);    // If no records "No Content" status
        }
        
        private function get_list_result($query) {
            $result = array();
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) {
                while($row = $r->fetch_assoc()) {
                    $result[] = $row;
                }
            }
            return $result;
        }

        private function get_category_result($query) {
            $result = array();
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) {
                while($row = $r->fetch_assoc()) {
                    $result = $row;
                }
            }
            return $result;
        }

        private function get_one_result($query) {
            $result = array();
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) $result = $r->fetch_assoc();
                return $result;
        }

        private function get_one($query) {
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) {
                $result = $r->fetch_assoc();
                $this->response($this->json($result), 200); // send user details
            }
            $this->response('',204);    // If no records "No Content" status
        }

        private function get_one_detail($query) {
            $result = array();
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) $result = $r->fetch_assoc();
            return $result;
        }       
        
        private function get_count($query) {
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) {
                $result = $r->fetch_row();
                $this->response($result[0], 200); 
            }
            $this->response('',204);    // If no records "No Content" status
        }
        
        private function get_count_result($query) {
            $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
            if($r->num_rows > 0) {
                $result = $r->fetch_row();
                return $result[0];
            }
            return 0;
        }
        
        private function post_one($obj, $column_names, $table_name) {
            $keys       = array_keys($obj);
            $columns    = '';
            $values     = '';
            foreach($column_names as $desired_key) { // Check the recipe received. If blank insert blank into the array.
              if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
                } else {
                    $$desired_key = $obj[$desired_key];
                }
                $columns    = $columns.$desired_key.',';
                $values     = $values."'".$this->real_escape($$desired_key)."',";
            }
            $query = "INSERT INTO ".$table_name."(".trim($columns,',').") VALUES(".trim($values,',').")";
            //echo "QUERY : ".$query;
            if(!empty($obj)) {
                //$r = $this->mysqli->query($query) or trigger_error($this->mysqli->errog.__LINE__);
                if ($this->mysqli->query($query)) {
                    $status = "success";
                $msg        = $table_name." created successfully";
                } else {
                    $status = "failed";
                $msg        = $this->mysqli->errog.__LINE__;
                }
                $resp = array('status' => $status, "msg" => $msg, "data" => $obj);
                $this->response($this->json($resp),200);
            } else {
                $this->response('',204);    //"No Content" status
            }
        }

        private function post_update($id, $obj, $column_names, $table_name) {
            $keys = array_keys($obj[$table_name]);
            $columns = '';
            $values = '';
            foreach($column_names as $desired_key){ // Check the recipe received. If key does not exist, insert blank into the array.
              if(!in_array($desired_key, $keys)) {
                $$desired_key = '';
                } else {
                    $$desired_key = $obj[$table_name][$desired_key];
                }
                $columns = $columns.$desired_key."='".$this->real_escape($$desired_key)."',";
            }

            $query = "UPDATE ".$table_name." SET ".trim($columns,',')." WHERE id=$id";
            if(!empty($obj)) {
                // $r = $this->mysqli->query($query) or die($this->mysqli->errog.__LINE__);
                if ($this->mysqli->query($query)) {
                    $status = "success";
                    $msg    = $table_name." update successfully";
                } else {
                    $status = "failed";
                    $msg    = $this->mysqli->errog.__LINE__;
                }
                $resp = array('status' => $status, "msg" => $msg, "data" => $obj);
                $this->response($this->json($resp),200);
            } else {
                $this->response('',204);    // "No Content" status
            }
        }

        private function delete_one($id, $table_name) {
            if($id > 0) {
                $query="DELETE FROM ".$table_name." WHERE id = $id";
                if ($this->mysqli->query($query)) {
                    $status = "success";
                $msg        = "One record " .$table_name." successfully deleted";
                } else {
                    $status = "failed";
                $msg        = $this->mysqli->errog.__LINE__;
                }
                $resp = array('status' => $status, "msg" => $msg);
                $this->response($this->json($resp),200);
            } else {
                $this->response('',204);    // If no records "No Content" status
            }
        }
        
        private function responseInvalidParam() {
            $resp = array("status" => 'Failed', "msg" => 'Invalid Parameter' );
            $this->response($this->json($resp), 200);
        }

        /* ==================================== End of API utilities ==========================================
         * ====================================================================================================
         */

        /* Encode array into JSON */
        private function json($data) {
            if(is_array($data)) {
                // return json_encode($data, JSON_NUMERIC_CHECK);
                return json_encode($data);
            }
        }

        /* String mysqli_real_escape_string */
        private function real_escape($s) {
            return mysqli_real_escape_string($this->mysqli, $s);
        }
    }

    // Initiiate Library

    $api = new API;
    if (isset($_GET['get_wallpapers'])) {
        $api->get_wallpapers();
    } else if (isset($_GET['get_one_wallpaper'])) {
        $api->get_one_wallpaper();
    } else if (isset($_GET['get_categories'])) {
        $api->get_categories();
    } else if (isset($_GET['get_category_details'])) {
        $api->get_category_details();
    } else if (isset($_GET['get_search'])) {
        $api->get_search();
    } else if (isset($_GET['get_search_category'])) {
        $api->get_search_category();
    } else if (isset($_GET['update_view'])) {
        $api->update_view();
    } else if (isset($_GET['update_download'])) {
        $api->update_download();
    } else if (isset($_GET['get_ads'])) {
        $api->get_ads();
    } else if (isset($_GET['get_settings'])) {
        $api->get_settings();
    } else {
        $api->processApi();
    }
    
?>
