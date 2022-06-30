<?php
class UserController extends BaseController
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    /**
     * "/user/list" Endpoint - Get list of users
     */
    public function listAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $arrQueryStringParams = $this->getQueryStringParams();
 
        if (strtoupper($requestMethod) == 'GET') {
            try {
                $userModel = new UserModel();
 
                $intLimit = 10;
                if (isset($arrQueryStringParams['limit']) && $arrQueryStringParams['limit']) {
                    $intLimit = $arrQueryStringParams['limit'];
                }
 
                $arrUsers = $userModel->getUsers($intLimit);
                $responseData = json_encode($arrUsers);
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }
        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }
 
        // send output
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)), 
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }

    /**
     * "/user/login" Endpoint - login req
     */
    public function loginAction()
    {
        $strErrorDesc = '';
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $responseData = null;

        if (strtoupper($requestMethod) == 'POST') {
            $_POST = json_decode(file_get_contents('php://input'), true);
            if (!isset($_POST['username']) || !isset($_POST['password'])){
                $this->sendOutput(json_encode(array('error' => 'Expect username & password and both not empty : ' . json_encode($_POST))),
                    array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
                );
            }

            try {
                $targetUser = null;
                if (strrpos($_POST['username'], "@") === false) {
                    $targetUser = $this->db->select("SELECT * FROM users WHERE username = '" . $_POST['username'] . "'");
                }else{
                    $targetUser = $this->db->select("SELECT * FROM users WHERE user_email = '" . $_POST['username'] . "'");
                }

                if ($targetUser){
                    $targetUser = $targetUser[0];
                    if ($targetUser['user_status'] == 0){
                        $this->sendOutput(json_encode(array('error' => 'User disabled')),
                            array('Content-Type: application/json', 'HTTP/1.1 500 Internal Server Error')
                        );
                    }

                    $responseData = json_encode([
                        'status' => 'ok',
                        'code' => 200,
                        'data' => $targetUser
                    ]);
                }else{
                    $responseData = json_encode([
                        'status' => 'ko',
                        'code' => 404,
                        'message' => 'user not found with credentials given!'
                    ]);
                }
            } catch (Error $e) {
                $strErrorDesc = $e->getMessage().'Something went wrong! Please contact support.';
                $strErrorHeader = 'HTTP/1.1 500 Internal Server Error';
            }



        } else {
            $strErrorDesc = 'Method not supported';
            $strErrorHeader = 'HTTP/1.1 422 Unprocessable Entity';
        }

        // send output
        if (!$strErrorDesc) {
            $this->sendOutput(
                $responseData,
                array('Content-Type: application/json', 'HTTP/1.1 200 OK')
            );
        } else {
            $this->sendOutput(json_encode(array('error' => $strErrorDesc)),
                array('Content-Type: application/json', $strErrorHeader)
            );
        }
    }
}