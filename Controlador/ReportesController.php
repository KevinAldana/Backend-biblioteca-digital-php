<?php
require_once('../configDB.php'); // Incluir configuración de PDO
require_once('../Modelo/reporte.php'); 

class ReportesController {

    private $reportes;

    public function __construct($pdo) {
        $this->reportes = new Reportes($pdo);
    }

    public function manejarSolicitud($tipoInforme) {
        switch ($tipoInforme) {
            case 'prestamos':
                $this->reportes->generarInformePrestamos();
                break;
            case 'inventario':
                $this->reportes->generarInformeInventario();
                break;
            default:
                http_response_code(400);
                echo "Tipo de informe no válido";
                break;
        }
    }
    
}

// Configuración PDO y manejo de solicitudes
$controller = new ReportesController($pdo);
$tipoInforme = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$controller->manejarSolicitud($tipoInforme);
?>
