<?php
require_once('../vendor/tecnickcom/tcpdf/tcpdf.php');

class Reportes {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function generarInformePrestamos() {
        $sql = "SELECT 
                    r.titulo, 
                    r.autor, 
                    u.nombre as usuario,
                    p.fecha_prestamo, 
                    p.fecha_devolucion 
                FROM 
                    prestamos p 
                    JOIN recursos r ON p.id_recurso = r.id 
                    JOIN usuarios u ON p.id_usuario = u.id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Crear nuevo PDF
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetMargins(15, 20, 10); // Márgenes: izquierda, arriba, derecha
        $pdf->SetFont('helvetica', '', 12);

        // Título del PDF
        $pdf->Cell(0, 10, 'Informe de Préstamos', 0, 1, 'C');

        // Crear tabla en PDF
        $pdf->SetFillColor(200, 220, 255);
        $pdf->SetFont('helvetica', 'B', 12); // Negrita para los encabezados
        $pdf->Cell(40, 10, 'Título', 1, 0, 'C', 1);
        $pdf->Cell(50, 10, 'Autor', 1, 0, 'C', 1);
        $pdf->Cell(30, 10, 'Usuario', 1, 0, 'C', 1);
        $pdf->Cell(35, 10, 'Fecha Préstamo', 1, 0, 'C', 1);
        $pdf->Cell(35, 10, 'Fecha Devolución', 1, 1, 'C', 1);

        $pdf->SetFont('helvetica', '', 12); // Regular para el contenido

        foreach ($resultados as $fila) {
            // Formatear fechas
            $fechaPrestamo = date('Y-m', strtotime($fila['fecha_prestamo']));
            $fechaDevolucion = date('Y-m', strtotime($fila['fecha_devolucion']));
            
            $pdf->Cell(40, 10, $fila['titulo'], 1);
            $pdf->Cell(50, 10, $fila['autor'], 1);
            $pdf->Cell(30, 10, $fila['usuario'], 1);
            $pdf->Cell(35, 10, $fechaPrestamo, 1);
            $pdf->Cell(35, 10, $fechaDevolucion, 1);
            $pdf->Ln();
        }

        // Descargar PDF
        $pdf->Output('informe_prestamos.pdf', 'D');
    }

    public function generarInformeInventario() {
        $sql = "SELECT 
                    titulo, 
                    autor, 
                    genero, 
                    anio_publicacion 
                FROM 
                    recursos";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Crear nuevo PDF
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetMargins(15, 20, 15); // Márgenes: izquierda, arriba, derecha
        $pdf->SetFont('helvetica', '', 12);
    
        // Título del PDF
        $pdf->Cell(0, 10, 'Informe de Inventario', 0, 1, 'C');
    
        // Crear tabla en PDF
        $pdf->SetFillColor(200, 220, 255);
        $pdf->SetFont('helvetica', 'B', 12); // Negrita para los encabezados
        $pdf->Cell(60, 10, 'Título', 1, 0, 'C', 1);
        $pdf->Cell(50, 10, 'Autor', 1, 0, 'C', 1);
        $pdf->Cell(35, 10, 'Género', 1, 0, 'C', 1);
        $pdf->Cell(45, 10, 'Año de publicación', 1, 1, 'C', 1);
    
        $pdf->SetFont('helvetica', '', 12); // Regular para el contenido
    
        foreach ($resultados as $fila) {
            // Formatear fecha
            $fechaAdquisicion = date('Y-m', strtotime($fila['anio_publicacion']));
    
            $pdf->Cell(60, 10, $fila['titulo'], 1);
            $pdf->Cell(50, 10, $fila['autor'], 1);
            $pdf->Cell(35, 10, $fila['genero'], 1);
            $pdf->Cell(45, 10, $fechaAdquisicion, 1);
            $pdf->Ln();
        }
    
        // Descargar PDF
        $pdf->Output('informe_inventario.pdf', 'D');
    }

    public function manejarSolicitud($tipoInforme) {
        if ($tipoInforme == 'prestamos') {
            $this->generarInformePrestamos();
        } elseif ($tipoInforme == 'inventario') {
            $this->generarInformeInventario();
        } else {
            echo 'Tipo de informe no válido.';
        }
    }
}
?>
