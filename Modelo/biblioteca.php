<?php
require '../configDB.php'; 

class Biblioteca {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Función para registrar un nuevo usuario
    public function registroUsuario($nombre, $email, $contraseña, $rol) {
        try {
            $contraseña = password_hash($contraseña, PASSWORD_DEFAULT);
            $stmt       = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $usuario = $stmt->fetch();
        
            if ($usuario)
                return 0;
            else {
                $stmt = $this->pdo->prepare('INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES (:nombre, :email, :contraseña, :rol)');
                $stmt->bindParam(':nombre'    , $nombre);
                $stmt->bindParam(':email'     , $email);
                $stmt->bindParam(':contraseña', $contraseña);
                $stmt->bindParam(':rol'       , $rol);
                $stmt->execute();
                return 1;
            }
        } catch (PDOException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    // Función para iniciar sesión
    public function inicioSesion($email, $contraseña) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = :email');
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($contraseña, $usuario['contraseña'])) {
                session_start();
                $_SESSION['user_id']  = $usuario['id'];
                $_SESSION['user_rol'] = $usuario['rol'];
                return 1;
            } else
                return 0;
        } catch (PDOException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    // Función para agregar o actualizar recursos
    public function administrarRecursos($data) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO 
                    recursos (titulo, autor, genero, anio_publicacion, isbn, tipo, disponibilidad) 
                VALUES 
                    (:titulo, :autor, :genero, :anio_publicacion, :isbn, :tipo, :disponibilidad) 
                ON DUPLICATE KEY UPDATE 
                    titulo          = VALUES(titulo), autor         = VALUES(autor), genero = VALUES(genero),
                    anio_publicacion = VALUES(anio_publicacion), isbn = VALUES(isbn), tipo    = VALUES(tipo), disponibilidad = VALUES(disponibilidad)
            ');
            $stmt->bindParam(':titulo'          , $data['titulo']);
            $stmt->bindParam(':autor'           , $data['autor']);
            $stmt->bindParam(':genero'          , $data['genero']);
            $stmt->bindParam(':anio_publicacion', $data['anio']);
            $stmt->bindParam(':isbn'            , $data['isbn']);
            $stmt->bindParam(':tipo'            , $data['tipo']);
            $stmt->bindParam(':disponibilidad'  , $data['disponibilidad']);
            $stmt->execute();
            return 1;
        } catch (PDOException $e) {
            return 0 . $e->getMessage();
        }
    }
    // Función para agregar reseñas
    public function agregarReview($usuarioId, $recursoId, $calificacion, $comentario) {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO reseñas (id_usuario, id_recurso, calificacion, comentario) VALUES (:usuarioId, :recursoId, :calificacion, :comentario)');
            $stmt->bindParam(':usuarioId'   , $usuarioId);
            $stmt->bindParam(':recursoId'   , $recursoId);
            $stmt->bindParam(':calificacion', $calificacion);
            $stmt->bindParam(':comentario'  , $comentario);
            $stmt->execute();
        
            return 1;
        } catch (PDOException $e) {
            return 0 . $e->getMessage();
        }
    }
    // Funcion para obtener el usuario
    public function obtenerUsuarioPorEmail($email) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new PDOException('Error: ' . $e->getMessage());
        }
    }
    // Funcion para obtener todos los usuarios
    public function obtenerUsuariosAdmin() {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios');
            $stmt->execute();
            $datos = [];
            while ($row = $stmt->fetch())
                $datos[] = $row;
            return $datos;
        } catch (Exception $e) {
            throw new PDOException('Error: ' . $e->getMessage());
        }
    }
    // Función encargada de obtener todas las colecciones (Libros)
    public function obtenerColecciones() {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM recursos');
            $stmt->execute();
            $datos = [];
            while ($row = $stmt->fetch())
                $datos[] = $row;
            return $datos;
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }
    // Función para actualizar colección
    public function updateColeccion($autor, $titulo, $genero, $anio, $isbn, $id) {
        try {
            // Prepara la consulta SQL para actualizar el recurso específico por ID
            $stmt = $this->pdo->prepare('
                UPDATE 
                    recursos 
                SET
                    titulo           = :titulo, 
                    autor            = :autor, 
                    genero           = :genero, 
                    anio_publicacion = :anio, 
                    isbn             = :isbn 
                WHERE 
                    id = :id
            ');
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':autor' , $autor);
            $stmt->bindParam(':genero', $genero);
            $stmt->bindParam(':anio'  , $anio);
            $stmt->bindParam(':isbn'  , $isbn);
            $stmt->bindParam(':id'    , $id, PDO::PARAM_INT);
            $stmt->execute();
            return 1;
        } catch (PDOException $e) {
            return 0 . $e->getMessage();
        }
    }

    // Función para actualizar usuario
    public function actualizarUsuario($nombre, $email, $rol, $id, $contraseña = '', $preferencias = null) {
        try {
            $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, rol = :rol";
            if (!empty($contraseña)) {
                $contraseñaHash = password_hash($contraseña, PASSWORD_BCRYPT);
                $sql .= ", contraseña = :contraseña";
            }
            if ($preferencias !== null)
                $sql .= ", preferencias = :preferencias";

            $sql .= " WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':rol', $rol);
            $stmt->bindParam(':id', $id);
            if (!empty($contraseña))
                $stmt->bindParam(':contraseña', $contraseñaHash);
            if ($preferencias !== null)
                $stmt->bindParam(':preferencias', $preferencias);
            $stmt->execute();
            return 1;
        } catch (PDOException $e) {
            return 0 . $e->getMessage();
        } catch (Exception $e) {
            return ['message' => 'Error inesperado: ' . $e->getMessage()];
        }
    }

    public function eliminarUsuario($id) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return 1;
        } catch (PDOException $e) {
            return 0 . $e->getMessage();
        }
    }
    public function solicitarPrestamo($recursoId, $fechaSolicitud, $usuarioId) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO prestamos (
                    id_usuario,
                    id_recurso,
                    fecha_prestamo,
                    estado
                )
                VALUES (
                    :usuarioId,
                    :recurso_id,
                    :fecha_solicitud,
                    "prestado"
                )
            ');
            $stmt->bindParam(':recurso_id'     , $recursoId);
            $stmt->bindParam(':fecha_solicitud', $fechaSolicitud);
            $stmt->bindParam(':usuarioId'      , $usuarioId);
            $stmt->execute();
            return 1;
        } catch (Exception $e) {
            return 0 . $e->getMessage();
        }
    }
    public function devolverRecurso($prestamoId) {
        try {
            $stmt = $this->pdo->prepare('
                UPDATE 
                    prestamos
                SET 
                    fecha_devolucion = NOW(), 
                    estado           = "devuelto"
                WHERE 
                    id = :prestamoId
            ');
            $stmt->bindParam(':prestamoId', $prestamoId, PDO::PARAM_INT);
            $stmt->execute();
            return 1;
        } catch (Exception $e) {
            return 0 . $e->getMessage();
        }
    }
    public function obtenerPrestamos() {
        try {
            $stmt = $this->pdo->prepare('
                SELECT 
                    prestamos.id, 
                    recursos.titulo, 
                    prestamos.fecha_prestamo, 
                    prestamos.fecha_devolucion
                FROM 
                    prestamos
                    JOIN recursos ON prestamos.id_recurso = recursos.id
                WHERE 
                    prestamos.estado    = "prestado" 
                    OR prestamos.estado = "devuelto"
            ');
            $stmt->execute();
            $datos = [];
            while ($row = $stmt->fetch())
                $datos[] = $row;
            return $datos;
        } catch (PDOException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    // Funcion para obtener el usuario por ID
    public function obtenerUsuarioPorId($id) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception('Error: ' . $e->getMessage());
        }
    }
    public function obtenerRecursosPrestados($id_usuario) {
        try {
            $sql = "SELECT 
                        r.id AS id_recurso, 
                        r.titulo,
                        r.autor,
                        r.genero,
                        p.fecha_prestamo
                    FROM 
                        prestamos p
                        JOIN recursos r ON p.id_recurso = r.id
                    WHERE 
                        p.id_usuario = ? 
                        AND p.estado = 'prestado'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id_usuario]);
            while ($row = $stmt->fetch())
                $datos[] = $row;
            return $datos;
        } catch (PDOException $e) {
            return [];
        }
    }
}

// Instanciar la clase Biblioteca
$biblioteca = new Biblioteca($pdo);
?>
