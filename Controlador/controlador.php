<?php
require '../Modelo/biblioteca.php';
require '../vendor/autoload.php'; 
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

class Controlador {
    private $biblioteca;
    private $data;
    private $key;

    public function __construct($pdo) {
        $this->biblioteca = new Biblioteca($pdo);
        $this->data       = json_decode(file_get_contents('php://input'), true);
        $this->key        = $_ENV['JWT_SECRET_KEY'];
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'login':
                $this->login();
            break;
            case 'registro':
                $this->registro();
            break;
            case 'agregarColeccion':
                $this->administrarRecursos();
            break;
            case 'requestPrestamo':
                $this->solicitarPrestamo();
            break;
            case 'returnResource':
                $this->devolverRecurso();
            break;
            case 'getPrestamos':
                $this->obtenerPrestamos();
            break;
            case 'crearReview':
                $this->crearReview();
            break;
            case 'obtenerUsuarios':
                $this->obtenerUsuarios();
            break;
            case 'getColeccion':
                $this->obtenerColeccionesAdmin();
            break;
            case 'updateColeccion':
                $this->actualizarColeccion();
            break;
            case 'updateUser':
                $this->actualizarUsuario();
            break;
            case 'deleteUser':
                $this->eliminarUsuario();
            break;    
            case 'getUsuario':
                $this->obtenerUsuarioId();
            break;
            case 'getRecursosPrestados':
                $this->getRecursosPrestados();
            break;
            default:
                echo json_encode(['message' => 'Acción no válida.']);
            break;
        }
    } 

    private function login() {
        $email    = $this->data['email']    ?? '';
        $password = $this->data['password'] ?? '';
        
        $response = $this->biblioteca->inicioSesion($email, $password);
        if ($response === 1) {
            $usuario = $this->biblioteca->obtenerUsuarioPorEmail($email); // 
            $payload = [
                'iss' => 'http://localhost:4200',
                'aud' => 'http://localhost:4200',
                'iat' => time(),
                'exp' => time() + (60 * 60),
                'data' => [
                    'id'       => $usuario['id'],
                    'rol'      => $usuario['rol'],
                    'email'    => $usuario['email'],
                    'username' => $usuario['nombre']
                ]
            ];
            $jwt = JWT::encode($payload, $this->key, 'HS256');
            echo json_encode(['token' => $jwt, 'rol' => $usuario['rol'], 'username' => $usuario['nombre'], 'id' => $usuario['id']]);
        } else
            echo json_encode(['message' => 'Credenciales incorrectas']);
    }

    private function registro() {
        $name     = $this->data['nombre']   ?? '';
        $email    = $this->data['email']    ?? '';
        $password = $this->data['password'] ?? '';
        $rol      = $this->data['rol']      ?? '';

        $response = $this->biblioteca->registroUsuario($name, $email, $password, $rol);
        echo json_encode(['message' => $response]);
    }

    private function administrarRecursos() {
        $response = $this->biblioteca->administrarRecursos($this->data);
        echo json_encode($response);
    }

    private function solicitarPrestamo() {
        $recursoId      = $this->data['id']      ?? '';
        $fechaSolicitud = $this->data['fechaSolicitud'] ?? '';
        $id_usuario     = $this->data['usuarioId']      ?? '';
    
        $response = $this->biblioteca->solicitarPrestamo($recursoId, $fechaSolicitud, $id_usuario);
        echo json_encode($response);
    }
    
    private function devolverRecurso() {
        $prestamo_id = $this->data['id'] ?? '';
    
        $response = $this->biblioteca->devolverRecurso($prestamo_id);
        echo json_encode($response);
    }
    
    private function obtenerPrestamos() {
        $response = $this->biblioteca->obtenerPrestamos();
        echo json_encode([$response]);
    }


    private function crearReview() {
        $usuarioId    = $this->data['id_usuario']    ?? '';
        $rescursoId   = $this->data['id_recurso']   ?? '';
        $calificacion = $this->data['calificacion'] ?? '';
        $comentario   = $this->data['comentario']   ?? '';

        $response = $this->biblioteca->agregarReview($usuarioId, $rescursoId, $calificacion, $comentario);
        echo json_encode($response);
    }

    private function obtenerUsuarios() {
        $response = $this->biblioteca->obtenerUsuariosAdmin();
        echo json_encode([$response]);
    }
    private function obtenerColeccionesAdmin() {
        $response = $this->biblioteca->obtenerColecciones();
        echo json_encode([$response]);
    }
    private function actualizarColeccion() {
        $autor  = $this->data['autor']            ?? '';
        $titulo = $this->data['titulo']           ?? '';
        $genero = $this->data['genero']           ?? '';
        $anio   = $this->data['anio_publicacion'] ?? '';
        $isbn   = $this->data['isbn']             ?? '';
        $id     = $this->data['id']               ?? '';

        $response = $this->biblioteca->updateColeccion($autor, $titulo, $genero, $anio, $isbn, $id);
        echo json_encode($response);
    }
    private function actualizarUsuario() {
        $nombre       = $this->data['nombre']   ?? '';
        $email        = $this->data['email']    ?? '';
        $rol          = $this->data['rol']      ?? '';
        $id           = $this->data['id']       ?? '';
        $password     = $this->data['password'] ?? '';
        $preferencias = $this->data['preferencias'] ?? '';

        $response = $this->biblioteca->actualizarUsuario($nombre, $email, $rol, $id, $password, $preferencias);
        echo json_encode($response);
    }
    private function eliminarUsuario() {
        $id = $_GET['id'] ?? '';
        if (empty($id))
            return 0;
        
        $response = $this->biblioteca->eliminarUsuario($id);
        echo json_encode($response);
    }
    private function obtenerUsuarioId() {
        $id       = $this->data['id'] ?? '';
        $response = $this->biblioteca->obtenerUsuarioPorId($id);
        echo json_encode([$response]);
    }
    private function getRecursosPrestados() {
        $id_usuario = $this->data['usuarioId'] ?? '';
        if ($id_usuario) {
            $recursos = $this->biblioteca->obtenerRecursosPrestados($id_usuario);
            echo json_encode([$recursos]);
        } else {
            echo json_encode(['message' => 'Usuario no autenticado.']);
        }
    }
    
}

$controlador = new Controlador($pdo);
$controlador->handleRequest();
?>
