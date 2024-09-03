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
            $stmt       = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario)
                return 0;
            else {
                $stmt = $this->pdo->prepare('INSERT INTO usuarios (nombre, email, contraseña, rol) VALUES (?, ?, ?, ?)');
                $stmt->execute([$nombre, $email, $contraseña, $rol]);
                return 1;
            }
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    // Función para iniciar sesión
    public function inicioSesion($email, $contraseña) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            $usuario = $stmt->fetch();

            if ($usuario && password_verify($contraseña, $usuario['contraseña'])) {
                session_start();
                $_SESSION['user_id']  = $usuario['id'];
                $_SESSION['user_rol'] = $usuario['rol'];
                return 1;
            } else
                return 0;
        } catch (Exception $e) {
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
                    (?, ?, ?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                    titulo          = VALUES(titulo), autor         = VALUES(autor), genero = VALUES(genero),
                    anio_publicacion = VALUES(anio_publicacion), isbn = VALUES(isbn), tipo    = VALUES(tipo), disponibilidad = VALUES(disponibilidad)
            ');
            $stmt->execute([
                $data['titulo'], $data['autor'], $data['genero'], $data['anio'], $data['isbn'], $data['tipo'], $data['disponibilidad']
            ]);
            return 1;
        } catch (Exception $e) {
            return 0 . $e->getMessage();
        }
    }

    // Función para gestionar préstamos
    public function gestionarPrestamos($usuario_id, $recursoId) {
        try {
            $stmt = $this->pdo->prepare('SELECT disponibilidad FROM recursos WHERE id = ?');
            $stmt->execute([$recursoId]);
            $recurso = $stmt->fetch();

            if ($recurso && $recurso['disponibilidad']) {
                $stmt = $this->pdo->prepare('INSERT INTO prestamos (id_usuario, id_recurso) VALUES (?, ?)');
                $stmt->execute([$usuario_id, $recursoId]);

                $stmt = $this->pdo->prepare('UPDATE recursos SET disponibilidad = 0 WHERE id = ?');
                $stmt->execute([$recursoId]);

                return 'Préstamo solicitado exitosamente.';
            } else
                return 'Recurso no disponible.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    // Función para actualizar perfil de usuario
    public function actualizarPerfil($usuarioId, $nombre, $email) {
        try {
            $stmt = $this->pdo->prepare('UPDATE usuarios SET nombre = ?, email = ? WHERE id = ?');
            $stmt->execute([$nombre, $email, $usuarioId]);

            return 'Perfil actualizado exitosamente.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    // Función para agregar reseñas
    public function agregarReview($usuarioId, $recursoId, $calificacion, $comentario) {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO reseñas (id_usuario, id_recurso, calificacion, comentario) VALUES (?, ?, ?, ?)');
            $stmt->execute([$usuarioId, $recursoId, $calificacion, $comentario]);

            return 'Reseña agregada exitosamente.';
        } catch (Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    // Funcion para obtener el usuario
    public function obtenerUsuarioPorEmail($email) {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception('Error: ' . $e->getMessage());
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
            throw new Exception('Error: ' . $e->getMessage());
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
        } catch (Exception $e) {
            return 0 . $e->getMessage();
        }
    }
    // Función para actualizar usuario desde el perfil de adiministrador
    public function actualizarUsuario($nombre, $email, $rol, $id) {
        try {
            // Prepara la consulta SQL para actualizar el recurso específico por ID
            $stmt = $this->pdo->prepare('
                UPDATE 
                    usuarios 
                SET
                    nombre = :nombre, 
                    email  = :email, 
                    rol    = :rol
                WHERE 
                    id = :id
            ');
            $stmt->bindParam(':nombre', $nombre);
            $stmt->bindParam(':email' , $email);
            $stmt->bindParam(':rol'   , $rol);
            $stmt->bindParam(':id'    , $id, PDO::PARAM_INT);
            $stmt->execute();
            return 1;
        } catch (Exception $e) {
            return 0 . $e->getMessage();
        }
    }
    public function eliminarUsuario($id) {
        try {
            $stmt = $this->pdo->prepare('DELETE FROM usuarios WHERE id = :id');
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return 1;
        } catch (Exception $e) {
            return 0 . $e->getMessage();
        }
    }
}

// Instanciar la clase Biblioteca
$biblioteca = new Biblioteca($pdo);
?>
