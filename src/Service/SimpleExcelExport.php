<?php

namespace App\Service;

/**
 * Classe simple d'exportation Excel compatible avec les versions récentes de PHP
 */
class SimpleExcelExport
{
    private $data = [];
    private $headers = [];
    private $title = 'Export Excel';
    
    /**
     * Constructor
     */
    public function __construct($title = 'Export Excel')
    {
        $this->title = $title;
    }
    
    /**
     * Définir les en-têtes du tableau
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }
    
    /**
     * Ajouter une ligne de données
     */
    public function addRow($row)
    {
        $this->data[] = $row;
        return $this;
    }
    
    /**
     * Générer le fichier CSV et le retourner en téléchargement
     */
    public function export($filename = 'export.csv')
    {
        // Assurer que le fichier a l'extension .csv
        $lowercaseFilename = strtolower($filename);
        $csvExtension = '.csv';
        if (substr($lowercaseFilename, -strlen($csvExtension)) !== $csvExtension) {
            $filename .= $csvExtension;
        }
        
        // Définir les en-têtes HTTP
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Ouvrir le flux de sortie
        $output = fopen('php://output', 'w');
        
        // Ajouter la marque BOM UTF-8 pour Excel
        fputs($output, "\xEF\xBB\xBF");
        
        // Écrire les en-têtes
        if (!empty($this->headers)) {
            fputcsv($output, $this->headers, ';', '"');
        }
        
        // Écrire les données
        foreach ($this->data as $row) {
            fputcsv($output, $row, ';', '"');
        }
        
        // Fermer le flux
        fclose($output);
        exit;
    }
}
