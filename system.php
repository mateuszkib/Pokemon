<?php
session_start();

class MyDB extends SQLite3
{
    public function __construct()
    {
        $this->open('Pokemon.db');
    }
}

$db = new MyDB();
if (!$db) {
    echo $db->lastErrorMsg();
}

class Response
{
    public static function getResponse($type, $msg, $data = array())
    {
        $response = ['success' => $type, 'message' => $msg, 'data' => $data];
        echo json_encode($response);
        exit();
    }
}


class Auth
{
    private $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    public function register($data)
    {
        $email = $data['email'];
        $password = $data['password'];
        $passwordConfirm = $data['passwordConfirm'];

        if (empty($email) || empty($password) || empty($passwordConfirm)) {
            header('HTTP/1.1 400 Bad Request');
            Response::getResponse(false, 'Please fill all fields!');
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('HTTP/1.1 400 Bad Request');
            Response::getResponse(false, 'Invalid email format!');
        } else if (strlen($password) < 6) {
            header('HTTP/1.1 400 Bad Request');
            Response::getResponse(false, 'Password minimum legnth is 6');
        } else if ($password !== $passwordConfirm) {
            header('HTTP/1.1 400 Bad Request');
            Response::getResponse(false, 'Password are not the same!');
        } else {
            $stmt = $this->db->prepare('SELECT email FROM users WHERE email=:email');
            if (!$stmt) {
                header('HTTP/1.1 500 Internal Server Error');
                echo $this->db->lastErrorMsg();
            }
            $stmt->bindValue(':email', $email, SQLITE3_TEXT);
            $result = $stmt->execute();

            if ($result->fetchArray()) {
                header('HTTP/1.1 400 Bad Request');
                Response::getResponse(false, 'User with this email exist!');
            } else {
                $hashPassword = password_hash($password, PASSWORD_BCRYPT);

                $insert = $this->db->prepare('INSERT INTO users(email,password) VALUES(?,?)');

                if (!$insert) {
                    header('HTTP/1.1 500 Internal Server Error');
                    echo $this->db->lastErrorMsg();
                }

                $insert->bindParam(1, $email);
                $insert->bindParam(2, $hashPassword);
                $insert->execute();

                header('HTTP/1.1 201 Created');
                Response::getResponse(true, 'You are register! You can login now.');
            }
        }
    }

    public function login($data)
    {
        $email = $data['email'];
        $password = $data['password'];

        if (empty($email) || empty($password)) {
            header('HTTP/1.1 400 Bad Request');
            Response::getResponse(false, 'Please fill all fields');
        } else {
            $select = $this->db->prepare('SELECT * FROM users WHERE email=:email');
            if (!$select) {
                header('HTTP/1.1 500 Internal Server Error');
                echo $this->db->lastErrorMsg();
            }
            $select->bindValue(':email', $email, SQLITE3_TEXT);
            $result = $select->execute();
            $row = $result->fetchArray();

            if (!$row) {
                header('HTTP/1.1 400 Bad Request');
                Response::getResponse(false, 'User with this Email doesn\'t exist');
            } else {
                if (!password_verify($password, $row['password'])) {
                    header('HTTP/1.1 400 Bad Request');
                    Response::getResponse(false, 'Password incorrect!');
                } else {
                    $user = ['id' => $row['id'], 'email' => $row['email']];
                    $_SESSION['id'] = $row['id'];
                    $_SESSION['email'] = $row['email'];
                    header('HTTP/1.1 200 OK');
                    Response::getResponse(true, 'You are logged in', $user);
                }
            }
        }
    }

    public function isAuth()
    {
        $user = json_decode($_POST['user']);
        if (isset($_SESSION['id']) && $_SESSION['id'] === $user->id) {
            header('HTTP/1.1 200 OK');
            Response::getResponse(true, 'Authenticated');
        } else {
            header('HTTP/1.1 401 Unauthorized');
            Response::getResponse(false, 'Not Authenticated');
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
        header('HTTP/1.1 200 OK');
        Response::getResponse(true, 'You are logout');
    }
}

class Pokemon
{
    public $name;
    public $db;

    function __construct($name, $db)
    {
        $this->name = $name;
        $this->db = $db;
    }

    public function getPokemon()
    {
        if (empty($_GET['name'])) {
            header('HTTP/1.1 400 Bad Request');
            Response::getResponse(false, 'Please write name Pokemon');
        }
        if (isset($_SESSION['id'])) {
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://pokeapi.co/api/v2/pokemon/" . $this->name,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "cache-control: no-cache"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            if ($err) {
                header('HTTP/1.1 500 Internal Server Error');
                Response::getResponse(false, 'We have a problem with your request!', $err);
            }

            curl_close($curl);
            $pokemon = [];

            if ($response !== 'Not Found') {
                $data = json_decode($response, true);
                $pokemon['image'] = $data['sprites']['front_default'];
                $pokemon['name'] = $data['name'];
                foreach ($data['moves'] as $value) {
                    $pokemon['moves'][] = $value['move']['name'];
                }

                $stmt = $this->db->prepare('INSERT INTO actions(name,user_id) VALUES(?,?)');

                if (!$stmt) {
                    header('HTTP/1.1 500 Internal Server Error');
                    echo $this->db->lastErrorMsg();
                }

                $stmt->bindValue(1, $this->name, SQLITE3_TEXT);
                $stmt->bindValue(2, $_SESSION['id'], SQLITE3_INTEGER);
                $stmt->execute();

                header('HTTP/1.1 200 OK');
                Response::getResponse(true, 'Successfully get data', $pokemon);
            } else {
                header('HTTP/1.1 400 Bad Request');
                Response::getResponse(false, 'Pokemon doesn\'t exist');
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');
            Response::getResponse(false, 'You don\'t have access to this action!');
        }
    }
}

class Actions
{
    private $db;

    function __construct($db)
    {
        $this->db = $db;
    }

    public function getActions()
    {
        $actions = [];
        if (isset($_SESSION['id'])) {
            $stmt = $this->db->prepare('SELECT a.*, u.email FROM actions as a INNER JOIN users as u ON a.user_id=u.id ORDER BY date DESC');
            if (!$stmt) {
                header('HTTP/1.1 500 Internal Server Error');
                $this->db->lastErrorMsg();
            }

            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $actions[] = $row;
            }

            header('HTTP/1.1 200 OK');
            Response::getResponse(true, 'Successfully get data', $actions);
        } else {
            header('HTTP/1.1 401 Unauthorized');
            Response::getResponse(false, 'You don\'t have access to this action!');
        }
    }

    public function deleteAction($id)
    {
        if (isset($_SESSION['id'])) {
            $stmt = $this->db->prepare('DELETE FROM actions WHERE id=:id');
            if (!$stmt) {
                header('HTTP/1.1 500 Internal Server Error');
                $this->db->lastErrorMsg();
            }
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();

            header('HTTP/1.1 200 OK');
            Response::getResponse(true, 'Action was successfully deleted!');
        } else {
            header('HTTP/1.1 401 Unauthorized');
            Response::getResponse(false, 'You don\'t have access to this action!');
        }
    }
}

$auth = new Auth($db);
$actions = new Actions($db);

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'register') {
        $auth->register($_POST);
    } else if ($_POST['action'] === 'login') {
        $auth->login($_POST);
    } else if ($_POST['action'] === 'logout') {
        $auth->logout();
    } else if ($_POST['action'] === 'delete-action') {
        $actions->deleteAction($_POST['id']);
    } else if ($_POST['action'] === 'is-auth') {
        $auth->isAuth();
    }
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'get-actions') {
        $actions->getActions();
    } else if ($_GET['action'] === 'search-pokemon') {
        $pokemon = new Pokemon($_GET['name'], $db);
        $pokemon->getPokemon();
    }
}