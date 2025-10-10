<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | DomPDF Settings
    |--------------------------------------------------------------------------
    | 
    | Ye settings PDF generation ko control karti hain
    | Isko manually config folder mein dalna hai
    |
    */

    // PDF warnings show nahi karenge
    'show_warnings' => false,
    
    // Public path automatically detect hoga
    'public_path' => null,
    
    // HTML entities ko convert karenge
    'convert_entities' => true,
    
    // Orientation: portrait ya landscape
    'orientation' => 'portrait',
    
    'options' => [
        /**
         * Font directory - fonts yahan se load honge
         */
        'font_dir' => storage_path('fonts/'),
        
        /**
         * Font cache - fonts cache yahan store honge
         */
        'font_cache' => storage_path('fonts/'),
        
        /**
         * Temporary directory - temp files ke liye
         */
        'temp_dir' => sys_get_temp_dir(),
        
        /**
         * Root directory - security ke liye
         */
        'chroot' => realpath(base_path()),
        
        /**
         * Font subsetting disable (better compatibility)
         */
        'enable_font_subsetting' => false,
        
        /**
         * PDF backend - CPDF use karenge
         */
        'pdf_backend' => 'CPDF',
        
        /**
         * Media type
         */
        'default_media_type' => 'screen',
        
        /**
         * Paper size - A4 paper use karenge
         */
        'default_paper_size' => 'a4',
        
        /**
         * Paper orientation - portrait (vertical)
         */
        'default_paper_orientation' => 'portrait',
        
        /**
         * Default font family
         */
        'default_font' => 'sans-serif',
        
        /**
         * DPI - dots per inch (print quality)
         */
        'dpi' => 96,
        
        /**
         * PHP code execution disable (SECURITY)
         */
        'enable_php' => false,
        
        /**
         * JavaScript enable
         */
        'enable_javascript' => true,
        
        /**
         * Remote resources load kar sakte hain (images, CSS)
         */
        'enable_remote' => true,
        
        /**
         * Font height ratio
         */
        'font_height_ratio' => 1.1,
        
        /**
         * HTML5 parser enable karenge
         */
        'enable_html5_parser' => true,
        
        /**
         * Image DPI
         */
        'image_dpi' => 96,
        
        /**
         * Encoding
         */
        'encoding' => 'UTF-8',
    ],
];