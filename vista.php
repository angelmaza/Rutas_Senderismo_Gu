<?php

class View {
    private $id;
    private $title;
    private $phtml;

    public function __construct ($id = null, $title = null) {
        $this->id = $id;
        $this->title = $title;
        $this->phtml = '';
    }

    public function header($cabecera_mapa = ""): void {
        $phtml = "<!DOCTYPE html>
        <html lang='es'>
        <head>
        <meta http-equiv=\"Content-Type\" contenido=\"text/html;\" charset=\"utf-8\">
        <link rel=\"stylesheet\" type=\"text/css\" href=\"recursos/styles.css\">
        <title>{$this->title}</title>
        $cabecera_mapa
        </head>
        <body style='background-color:gray'>
        <main>
        <header>
                    <h1>Mapitas</h1>
                    <nav>
                        <a href='index.php'>Home</a>
                    </nav>
                    <br><br><br>
                </header>
        <article>";
        $this->phtml .= $phtml;
    }

    function select(array $list, string $button_action) : void { //button action por ej seria "poke"
        $phtml = "
        <form method=\"get\" action=\"index.php\">
            <select name=\"id\">";
        foreach($list as $item) {
            $phtml .= "<option value=\"{$item['value']}\">{$item['option']}</option>";
        }
        $phtml .= "</select>
            <button type=\"submit\" name=\"action\" value=\"$button_action\">OK</button>
        </form>";
        $this->phtml .= $phtml;
    }

    public function mostrarDivConTexto($texto) {
        $this->phtml .= "<div> $texto </div>";

    }

    public function mostrarElementoCompleto($elemento) {
        $this->phtml .= $elemento;
    }

    public function mostrarTablaTiempo($datos) {
        $this->phtml .= "<table>";
        $this->phtml .= "<tr><th>Parámetro</th><th>Valor</th></tr>";
    
        foreach ($datos as $clave => $valor) {
            $this->phtml .= "<tr><td><strong>" . $clave . "</strong></td><td>$valor</td></tr>";
        }
    
        $this->phtml .= "</table>";
        $this->phtml .= "";

    }


    public function error($error) {
        $this->phtml .= "<p style='color:red;'>Error: $error</p>";
    }

    public function not_found($item, $id) {
        $this->phtml .= "<p>$item con ID $id no encontrado.</p>";
    }

    public function no_action($action) {
        $this->phtml .= "<p>Acción '$action' no encontrada.</p>";
    }

    public function footer() {
        $this->phtml .= "</body></html>";
    }

    public function getPhtml() {
        return $this->phtml;
    }
}
?>