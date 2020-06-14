<?php

namespace App\Http\Controllers;

use Exception;
use Response;
use Validator;
use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\CapabilityProfile;
use Mike42\Escpos\CapabilityProfiles\DefaultCapabilityProfile;
use Softon\SweetAlert\Facades\SWAL;  

class ImprimirController extends Controller{
    
    public function sanear_string($string){
    
        $string = trim($string);
    
        $string = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );
    
        $string = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );
    
        $string = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );
    
        $string = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );
    
        $string = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );
    
        $string = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C',),
            $string
        );
    
        //Esta parte se encarga de eliminar cualquier caracter extraño
        $string = str_replace(
            array("", "¨", "º", "-", "~",
                "#", "@", "|", "!", "\"",
                "·", "$", "%", "&",
                "(", ")", "?", "'", "¡",
                "¿", "[", "^", "<code>", "]",
                "+", "}", "{", "¨", "´",
                ">", "< ", ";", ",", ":",
                "."),
            '',
            $string
        );
    
        return $string;
    }

    public function _array_key_last(array $array){
        return (!empty($array)) ? array_keys($array)[count($array)-1] : null;
    }

    public function addSpacesNumber($string = '', $valid_string_length = 0) {
        if (strlen($string) < $valid_string_length) {
            $spaces = $valid_string_length - strlen($string);
            for ($index1 = 0; $index1 < $spaces; $index1++) {
                $string = ' ' . $string ;
            }
        }
        return $string;
    }

    function addSpacesString($string = '', $valid_string_length = 0) {
        if (strlen($string) < $valid_string_length) {
            $spaces = $valid_string_length - strlen($string);
            for ($index1 = 0; $index1 < $spaces; $index1++) {
                $string = $string . ' ' ;
            }
        }
        return $string;
    }

    
    //venta regular
    public function cuentaRapidaRegular(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            //$printer->setTextSize(2,2);
            $printer->text('* * * CUENTA * * *');
            $printer->setTextSize(1,1);
            $printer->text("\n");
            $printer->setEmphasis();

            /*
            if($req['p_estado'] == 1){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
                $printer->setTextSize(2,2);
                $printer->text('* * * PRECUENTA * * *');
                $printer->setTextSize(1,1);
                $printer->text("\n");
                $printer->setEmphasis();
            }else{
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->setEmphasis(true);
                //$printer->setTextSize(2,2);
                $printer->text('* * * CUENTA * * *');
                $printer->setTextSize(1,1);
                $printer->text("\n");
                $printer->setEmphasis();
            }
            */
            $printer->text($req['e_razon_social']."\n");
            $printer->text($req['l_direccion']."\n");
            $printer->text('Telf: '.$req['l_telefono'].' / RUC: '. $req['e_codigo'] ."\n");
            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setEmphasis(false);
            $printer->text('Fecha: '.$req['p_fecha']."\n");
            $printer->text('Ambiente: '.$req['a_descripcion']."\n");
            $printer->text('Mesa: '.$req['m_descripcion']."\n");
            $printer->text('Mozo: '.$req['p_nombre']."\n");
            $printer->text('Pedido Nº: '.$req['numero_pedido']."\n");
            $printer->setEmphasis(true);
            $printer->setJustification();
            if($req['p_estado'] == 0){
                $printer->text("\n");
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            //self::sanear_string()

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Producto', 22) . self::addSpacesNumber('Cant.', 8) . self::addSpacesNumber('Precio', 8) . self::addSpacesNumber('Total', 10) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            $total = 0;

            $detalles = $req['detalles'];
            foreach ($detalles as $item) {

                $producto_lines = str_split(self::sanear_string($item['producto']),22);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,22);
                }
                
                $cantidad = str_split(number_format(round(floatval($item['cantidad']),2),2),8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesNumber($l,8);
                }
            
                $precio = str_split(number_format(round(floatval($item['precio']),2),2),8);
                foreach ($precio as $k => $l) {
                    $l = trim($l);
                    $precio[$k] = self::addSpacesNumber($l,8);
                }
                
                $total_mult = $item['cantidad'] * $item['precio'];
                $total = $total+$total_mult;
                $total_str = str_split(number_format(round(floatval($total_mult),2),2),10);
                foreach ($total_str as $k => $l) {
                    $l = trim($l);
                    $total_str[$k] = self::addSpacesNumber($l,10);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($producto_lines);
                $temp[] = count($cantidad);
                $temp[] = count($precio);
                $temp[] = count($total_str);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($precio[$i])) {
                        $line .= ($precio[$i]);
                    }
                    if (isset($total_str[$i])) {
                        $line .= ($total_str[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }


                //Para una segunda fila
                if($item['observacion']){
                    $stringObserv = '';
                    if(is_array($item['observacion'])){
                        foreach ($item['observacion'] as $keyObserv => $valueObserv) {
                            if(!empty(trim($valueObserv['descripcion']))){
                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                            }
                        }
                        if(empty(trim($stringObserv))){
                            $stringObserv = null;
                        }else{
                            $stringObserv = substr(($stringObserv),0,-2);
                        }
                    }else{
                        if(!empty(trim($item['observacion']))){
                            $stringObserv = $item['observacion'];
                        }else{
                            $stringObserv = null;
                        }
                    }
                    if(!empty(trim($stringObserv))){
                        $observacion_lines = str_split('- '.$stringObserv, 19);
                        foreach ($observacion_lines as $k => $l) {
                            $l = trim($l);
                            $observacion_lines[$k] = self::addSpacesString($l, 19);
                        }
                    
                        $counter = 0;
                        $temp = [];
                        $temp[] = count($observacion_lines);
                        $counter = max($temp);
                    
                        for ($i = 0; $i < $counter; $i++) {
                            $line = '';
                            if (isset($observacion_lines[$i])) {
                                $line .= ($observacion_lines[$i]);
                            }
                    
                            $printer->text(self::addSpacesString('',4).$line . "\n");
                        }
                    }
                }
            }
            $printer->selectPrintMode();
            
            if($req['cortesia']==0){
                $impuesto      = round(floatval($req['impuesto']),2);
                $base          = number_format($total/(1+($impuesto/100)),2);
                $impuesto_porc = number_format(($total-($total/(1+($impuesto/100)))),2);
                $total         = number_format($total,2);
            }else{
            
                $impuesto      = number_format(0,2);
                $base          = number_format(0,2);
                $impuesto_porc = number_format(0,2);
                $total         = number_format(0,2);
            }

            $printer->text(self::addSpacesNumber('Base:', 38) . self::addSpacesNumber($base, 10) . "\n");
            $printer->text(self::addSpacesNumber('Impuesto '.$impuesto.'%:', 38) . self::addSpacesNumber($impuesto_porc, 10) . "\n");

            $descuento = $req['descuento'];
            if($descuento){
                $printer->setTextSize(1,1);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);

                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($desc_tipo == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($desc_tipo == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }
                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
                $printer->setTextSize(1,1);
            }else{
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);
            }

            if($req['cortesia'] == 1){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("\n");
                $printer->text("******************************************"."\n");
                $printer->text("**************** CORTESIA ****************"."\n");
                $printer->text("******************************************"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }else{
                $pago_tipo = $req['pago_tipo'];
                if($pago_tipo){
                    $printer->text("\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->selectPrintMode();
                    $printer->setEmphasis(false);
                    //$printer->text('Tipo de Pago: '."\n");
                    $efectivo = floatval($pago_tipo['efectivo']);
                    $tarjeta = floatval($pago_tipo['tarjeta']);
                    $tipo_pago_text = 'Tipo de Pago';
                    $efectivo_text = '';
                    $tarjeta_text = '';
                    if($efectivo>0){
                        $efectivo_text = 'Efectivo: '.$efectivo;
                        //$printer->text('Efectivo: '.self::sanear_string($efectivo)."\n");
                    }
                    if($tarjeta>0){
                        $tarjeta_text = 'Tarjeta: '.$tarjeta;
                        //$printer->text('Tarjeta: '.self::sanear_string($tarjeta)."\n");
                    }
                    $propina = floatval($pago_tipo['propina']);
                    $propina_efec = floatval($pago_tipo['propina_efec']);
                    $propina_tarj = floatval($pago_tipo['propina_tarj']);
                    $propina_text = '';
                    $propina_efec_text = '';
                    $propina_tarj_text = '';
                    if($propina == 1){
                        $propina_text = 'Propina';
                        //$printer->text("\n");
                        //$printer->text('Propina: '."\n");
                        if($propina_efec>0){
                            $propina_efec_text = 'Efectivo: '.$propina_efec;
                            //$printer->text('Efectivo: '.self::sanear_string($propina_efec)."\n");
                        }
                        if($propina_tarj>0){
                            $propina_tarj_text = 'Tarjeta: '.$propina_tarj;
                            //$printer->text('Tarjeta: '.self::sanear_string($propina_tarj)."\n");
                        }
                    }
                    $printer->setEmphasis(true);
                    $printer->text(self::addSpacesString($tipo_pago_text, 24) . self::addSpacesString($propina_text, 24) . "\n");
                    $printer->setEmphasis(false);
                    $printer->text(self::addSpacesString($efectivo_text, 24) . self::addSpacesString($propina_efec_text, 24) . "\n");
                    $printer->text(self::addSpacesString($tarjeta_text, 24) . self::addSpacesString($propina_tarj_text, 24) . "\n");
                    $tipo_pago = floatval($pago_tipo['pago_tipo']);
                    $pago_con = floatval($pago_tipo['pago_con']);
                    $vuelto = $pago_con-floatval($efectivo+$propina_efec);
                    $tarjeta_id_text = $pago_tipo['tarjeta_id_text'];
                    if($tipo_pago == 1){
                        $printer->text("\n");
                        $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                        $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                    }else if($tipo_pago == 2){
                        $printer->text("\n");
                        $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                    }else if($tipo_pago == 3){
                        $printer->text("\n");
                        $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                        $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                        $printer->text("\n");
                        $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                    }
                }
            }

            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            if($req['p_estado'] != 1){
                $printer->text("\n");
                $printer->text('Cajero: ' . $req['p_c_nombre'] . "\n");
            }

            if($req['p_estado'] == 1){
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->selectPrintMode();
                $printer->setEmphasis(false);
                $printer->text("\n");
                $printer->text("RUC: ___________________________________________"."\n");
                $printer->text("Razon Social: __________________________________"."\n");
                $printer->text("________________________________________________"."\n");
                $printer->text("Direccion: _____________________________________"."\n");
                $printer->text("________________________________________________"."\n");
            }

            if($req['p_estado'] == 0){
                $printer->text("\n");
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Gracias por su compra!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            /*
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Este documento no posee ningun valor fiscal!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            */
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json($req);

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    public function documento_fiscalRegular(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            $cuenta = $req['cuenta'];
            $json_fact_elect = $req['json_fact_elect'];
            $html = $json_fact_elect['html'];

            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            //$printer->setTextSize(2,2);
            $printer->text('* * * CUENTA * * *');
            $printer->setTextSize(1,1);
            $printer->text("\n");
            $printer->setEmphasis();
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(self::sanear_string($cuenta['e_razon_social'])."\n");
            $printer->text(self::sanear_string($cuenta['l_direccion'])."\n");
            $printer->text('Telf: '.$cuenta['l_telefono'].' / RUC: '. $cuenta['e_codigo'] ."\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setEmphasis(false);
            $printer->text("\n");
            $printer->text('Fecha: '.$cuenta['p_fecha']."\n");
            $printer->text('Ambiente: '.$cuenta['a_descripcion']."\n");
            $printer->text('Mesa: '.$cuenta['m_descripcion']."\n");
            $printer->text('Mozo: '.$cuenta['p_nombre']."\n");
            $printer->text('Pedido Nº: '.$cuenta['numero_pedido']."\n");
            $printer->text("\n");
            $printer->setEmphasis(true);
            $printer->setJustification();
            $printer->setEmphasis(true);
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->text("ANULADO"."\n");
                $printer->text("\n");
            }
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(self::sanear_string($json_fact_elect['tipo_text']." ".$json_fact_elect['codigo'])."\n");
            $printer->setJustification();
            $printer->text("\n");
            $printer->setJustification();
            if($json_fact_elect['cliente_numero_de_documento']){
                $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                $printer->text($json_fact_elect['cliente_numero_de_documento']."\n");
                if(isset($json_fact_elect['cliente_direccion'])){
                    $printer->text(self::sanear_string($json_fact_elect['cliente_direccion'])."\n");
                }
                $printer->text("\n");
            }else{
                if($json_fact_elect['cliente_denominacion']){
                    $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                }
            }

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Producto', 22) . self::addSpacesNumber('Cant.', 8) . self::addSpacesNumber('Precio', 8) . self::addSpacesNumber('Total', 10) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            $total = 0;

            $detalles = $cuenta['detalles'];
            foreach ($detalles as $item) {

                $producto_lines = str_split(self::sanear_string($item['producto']),22);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,22);
                }
                
                $cantidad = str_split(number_format(round(floatval($item['cantidad']),2),2),8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesNumber($l,8);
                }
            
                $precio = str_split(number_format(round(floatval($item['precio']),2),2),8);
                foreach ($precio as $k => $l) {
                    $l = trim($l);
                    $precio[$k] = self::addSpacesNumber($l,8);
                }
                
                $total_mult = $item['cantidad'] * $item['precio'];
                $total = $total+$total_mult;
                $total_str = str_split(number_format(round(floatval($total_mult),2),2),10);
                foreach ($total_str as $k => $l) {
                    $l = trim($l);
                    $total_str[$k] = self::addSpacesNumber($l,10);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($producto_lines);
                $temp[] = count($cantidad);
                $temp[] = count($precio);
                $temp[] = count($total_str);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($precio[$i])) {
                        $line .= ($precio[$i]);
                    }
                    if (isset($total_str[$i])) {
                        $line .= ($total_str[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }

                //Para una segunda fila
                if($item['observacion']){
                    $stringObserv = '';
                    if(is_array($item['observacion'])){
                        foreach ($item['observacion'] as $keyObserv => $valueObserv) {
                            if(!empty(trim($valueObserv['descripcion']))){
                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                            }
                        }
                        if(empty(trim($stringObserv))){
                            $stringObserv = null;
                        }else{
                            $stringObserv = substr(($stringObserv),0,-2);
                        }
                    }else{
                        if(!empty(trim($item['observacion']))){
                            $stringObserv = $item['observacion'];
                        }else{
                            $stringObserv = null;
                        }
                    }
                    if(!empty(trim($stringObserv))){
                        $observacion_lines = str_split('- '.$stringObserv, 19);
                        foreach ($observacion_lines as $k => $l) {
                            $l = trim($l);
                            $observacion_lines[$k] = self::addSpacesString($l, 19);
                        }
                    
                        $counter = 0;
                        $temp = [];
                        $temp[] = count($observacion_lines);
                        $counter = max($temp);
                    
                        for ($i = 0; $i < $counter; $i++) {
                            $line = '';
                            if (isset($observacion_lines[$i])) {
                                $line .= ($observacion_lines[$i]);
                            }
                    
                            $printer->text(self::addSpacesString('',4).$line . "\n");
                        }
                    }
                }
                $counter = 0;
            }
            $printer->selectPrintMode();
            
            $impuesto      = round(floatval($cuenta['impuesto']),2);
            $base          = number_format($total/(1+($impuesto/100)),2);
            $impuesto_porc = number_format(($total-($total/(1+($impuesto/100)))),2);
            $total         = number_format($total,2);

            $printer->text(self::addSpacesNumber('Base:', 38) . self::addSpacesNumber($base, 10) . "\n");
            $printer->text(self::addSpacesNumber('Impuesto '.$impuesto.'%:', 38) . self::addSpacesNumber($impuesto_porc, 10) . "\n");

            $descuento = $cuenta['descuento'];
            if($descuento){
                $printer->setTextSize(1,1);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);

                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($desc_tipo == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($desc_tipo == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }
                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
                $printer->setTextSize(1,1);
            }else{
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);
            }

            $pago_tipo = $cuenta['pago_tipo'];
            if($pago_tipo){
                $printer->text("\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->selectPrintMode();
                $printer->setEmphasis(false);
                //$printer->text('Tipo de Pago: '."\n");
                $efectivo = floatval($pago_tipo['efectivo']);
                $tarjeta = floatval($pago_tipo['tarjeta']);
                $tipo_pago_text = 'Tipo de Pago';
                $efectivo_text = '';
                $tarjeta_text = '';
                if($efectivo>0){
                    $efectivo_text = 'Efectivo: '.$efectivo;
                    //$printer->text('Efectivo: '.self::sanear_string($efectivo)."\n");
                }
                if($tarjeta>0){
                    $tarjeta_text = 'Tarjeta: '.$tarjeta;
                    //$printer->text('Tarjeta: '.self::sanear_string($tarjeta)."\n");
                }
                $propina = floatval($pago_tipo['propina']);
                $propina_efec = floatval($pago_tipo['propina_efec']);
                $propina_tarj = floatval($pago_tipo['propina_tarj']);
                $propina_text = '';
                $propina_efec_text = '';
                $propina_tarj_text = '';
                if($propina == 1){
                    $propina_text = 'Propina';
                    //$printer->text("\n");
                    //$printer->text('Propina: '."\n");
                    if($propina_efec>0){
                        $propina_efec_text = 'Efectivo: '.$propina_efec;
                        //$printer->text('Efectivo: '.self::sanear_string($propina_efec)."\n");
                    }
                    if($propina_tarj>0){
                        $propina_tarj_text = 'Tarjeta: '.$propina_tarj;
                        //$printer->text('Tarjeta: '.self::sanear_string($propina_tarj)."\n");
                    }
                }
                $printer->setEmphasis(true);
                $printer->text(self::addSpacesString($tipo_pago_text, 24) . self::addSpacesString($propina_text, 24) . "\n");
                $printer->setEmphasis(false);
                $printer->text(self::addSpacesString($efectivo_text, 24) . self::addSpacesString($propina_efec_text, 24) . "\n");
                $printer->text(self::addSpacesString($tarjeta_text, 24) . self::addSpacesString($propina_tarj_text, 24) . "\n");
                $tipo_pago = floatval($pago_tipo['pago_tipo']);
                $pago_con = floatval($pago_tipo['pago_con']);
                $vuelto = $pago_con-floatval($efectivo+$propina_efec);
                $tarjeta_id_text = $pago_tipo['tarjeta_id_text'];
                if($tipo_pago == 1){
                    $printer->text("\n");
                    $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                    $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                }else if($tipo_pago == 2){
                    $printer->text("\n");
                    $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                }else if($tipo_pago == 3){
                    $printer->text("\n");
                    $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                    $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                    $printer->text("\n");
                    $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                }
            }

            $printer->text("\n");
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            if($json_fact_elect['cliente_numero_de_documento']){
                $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                $printer->text("\n");
            }
            //self::sanear_string($cuenta['p_c_nombre'])
            $printer->text('Cajero: ' . self::sanear_string($cuenta['p_c_nombre']) . "\n");

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('¡ Gracias por su compra !' . "\n");
            $printer->text("\n");
            $printer->text('Consulte su documento en el portal web:' . "\n");
            $printer->text('nubefact.com/20601363029' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json(true);

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    public function comandaRegular(Request $req) {
        try{
            $comandas = [];
            $impresoras = $req['impresoras'];
            $detalles = $req['detalles'];
            if(!empty($impresoras)){
                foreach ($impresoras as $key => $value) {
                    $impresora['ip'] = $value['ip'];
                    $items = [];
                    if(!empty($value['categorias'])){
                        foreach ($value['categorias'] as $keyCat => $valueCat) {
                            if(!empty($detalles)){
                                foreach ($detalles as $keyDet => $valueDet) {
                                    if( intval($valueCat) == intval($valueDet['categoria']) ){
                                        unset($valueDet['categoria']);
                                        unset($valueDet['date_created']);
                                        unset($valueDet['date_now']);
                                        unset($valueDet['dates_diff']);
                                        unset($valueDet['estado']);
                                        unset($valueDet['estado_eliminado']);
                                        unset($valueDet['fecha_atencion_plato']);
                                        unset($valueDet['fecha_despacho_plato']);
                                        unset($valueDet['fecha_registro']);
                                        unset($valueDet['fk_id_producto']);
                                        unset($valueDet['id']);
                                        unset($valueDet['id_users']);
                                        unset($valueDet['observ_eliminado']);
                                        unset($valueDet['observacion2']);
                                        unset($valueDet['platos_estado']);
                                        unset($valueDet['precio']);
                                        
                                        $stringObserv = '';
                                        if(is_array($valueDet['observacion'])){
                                            foreach ($valueDet['observacion'] as $keyObserv => $valueObserv) {
                                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                                            }
                                            if(empty(trim($stringObserv))){
                                                $valueDet['observacion'] = null;
                                            }else{
                                                $valueDet['observacion'] = substr(($stringObserv),0,-2);
                                            }
                                        }else{
                                            if(!empty(trim($valueDet['observacion']))){
                                                $stringObserv = $valueDet['observacion'];
                                            }else{
                                                $valueDet['observacion'] = null;
                                            }
                                        }
                                        array_push($items,$valueDet);
                                    }
                                }
                            }
                        }
                        if(!empty($items)){
                            $impresora['items'] = $items;
                            array_push($comandas,$impresora);
                        }
                    }
                }
            }
            if(!empty($comandas)){
                foreach ($comandas as $key => $value) {
                    self::imprimirComandasRegular($req,$value['items'],$value['ip']);
                }
            }
            //return Response::json($comandas);
            
        }catch(Exception $e){
            return Response::json($e);
        }
    }
    function imprimirComandasRegular($req,$detalles,$ip) {
        try{
            $connector = new NetworkPrintConnector($ip);
            //$profile = SimpleCapabilityProfile::getInstance();
            //$profile = CapabilityProfile::load("CP-850");
            //$profile = CustomCapabilityProfile::getInstance();
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("\n");
            $printer->text("\n");
            $printer->text("\n");
            $printer->text("\n");
            $printer->setTextSize(2,2);
            $printer->text('* * * MESAS * * *');
            $printer->setTextSize(1,1);
            $printer->text("\n");
            $printer->setEmphasis();
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("\n");
            $printer->text('Fecha: '.$req['p_fecha']."\n");
            $printer->text('Ambiente: '.$req['a_descripcion']."\n");
            $printer->text('Mesa: '.$req['m_descripcion']."\n");
            $printer->text('Mozo: '.$req['p_nombre']."\n");
            $printer->text('Pedido Nº: '.$req['numero_pedido']."\n");
            $printer->setEmphasis(true);
            $printer->text("\n");
            $printer->setJustification();

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Cant.', 8) . self::addSpacesString('Producto', 40) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            foreach ($detalles as $item) {
                //Current item ROW 1
                $printer->text("\n");
                
                $cantidad = str_split($item['cantidad'],8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesString($l,8);
                }

                $producto_lines = str_split(self::sanear_string($item['producto']),40);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,40);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($cantidad);
                $temp[] = count($producto_lines);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }

                //Para una segunda fila
                if($item['observacion']){
                    $observacion_lines = str_split('- '.self::sanear_string($item['observacion']), 36);
                    foreach ($observacion_lines as $k => $l) {
                        $l = trim($l);
                        $observacion_lines[$k] = self::addSpacesString($l, 36);
                    }
                
                    $counter = 0;
                    $temp = [];
                    $temp[] = count($observacion_lines);
                    $counter = max($temp);
                
                    for ($i = 0; $i < $counter; $i++) {
                        $line = '';
                        if (isset($observacion_lines[$i])) {
                            $line .= ($observacion_lines[$i]);
                        }
                
                        $printer->text(self::addSpacesString('',12).$line . "\n");
                    }
                }
                $printer->text("\n");
                //Fin de la segunda fila
            }
            $printer->selectPrintMode();
            $printer->text("\n");

            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json('$profile');

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    
    //venta rapida
    public function cuentaRapida(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $printer = new Printer($connector);
            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text($req['e_razon_social']."\n");
            $printer->text($req['l_direccion']."\n");
            $printer->text('Telf: '.$req['l_telefono'].' / RUC: '. $req['e_codigo'] ."\n");
            $printer->text('Fecha: '.$req['p_fecha']."\n");
            $printer->text("\n");
            $printer->setEmphasis(true);
            $printer->text('Cliente: '.self::sanear_string($req['p_cliente'])."\n");
            $printer->setJustification();
            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            //self::sanear_string()

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Producto', 22) . self::addSpacesNumber('Cant.', 8) . self::addSpacesNumber('Precio', 8) . self::addSpacesNumber('Total', 10) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            $total = 0;

            $detalles = $req['detalles'];
            foreach ($detalles as $item) {
                //Current item ROW 1

                $producto_lines = str_split(self::sanear_string($item['producto']),22);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,22);
                }
                
                $cantidad = str_split(number_format(round(floatval($item['cantidad']),2),2),8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesNumber($l,8);
                }
            
                $precio = str_split(number_format(round(floatval($item['precio']),2),2),8);
                foreach ($precio as $k => $l) {
                    $l = trim($l);
                    $precio[$k] = self::addSpacesNumber($l,8);
                }
                
                $total_mult = $item['cantidad'] * $item['precio'];
                $total = $total+$total_mult;
                $total_str = str_split(number_format(round(floatval($total_mult),2),2),10);
                foreach ($total_str as $k => $l) {
                    $l = trim($l);
                    $total_str[$k] = self::addSpacesNumber($l,10);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($producto_lines);
                $temp[] = count($cantidad);
                $temp[] = count($precio);
                $temp[] = count($total_str);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($precio[$i])) {
                        $line .= ($precio[$i]);
                    }
                    if (isset($total_str[$i])) {
                        $line .= ($total_str[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }


                //Para una segunda fila
                if($item['observacion']){
                    $stringObserv = '';
                    if(is_array($item['observacion'])){
                        foreach ($item['observacion'] as $keyObserv => $valueObserv) {
                            if(!empty(trim($valueObserv['descripcion']))){
                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                            }
                        }
                        if(empty(trim($stringObserv))){
                            $stringObserv = null;
                        }else{
                            $stringObserv = substr(($stringObserv),0,-2);
                        }
                    }else{
                        if(!empty(trim($item['observacion']))){
                            $stringObserv = $item['observacion'];
                        }else{
                            $stringObserv = null;
                        }
                    }
                    if(!empty(trim($stringObserv))){
                        $observacion_lines = str_split('- '.$stringObserv, 19);
                        foreach ($observacion_lines as $k => $l) {
                            $l = trim($l);
                            $observacion_lines[$k] = self::addSpacesString($l, 19);
                        }
                    
                        $counter = 0;
                        $temp = [];
                        $temp[] = count($observacion_lines);
                        $counter = max($temp);
                    
                        for ($i = 0; $i < $counter; $i++) {
                            $line = '';
                            if (isset($observacion_lines[$i])) {
                                $line .= ($observacion_lines[$i]);
                            }
                    
                            $printer->text(self::addSpacesString('',4).$line . "\n");
                        }
                    }
                }
            }
            $printer->selectPrintMode();
            
            if($req['cortesia']==0){
                $impuesto      = round(floatval($req['impuesto']),2);
                $base          = number_format($total/(1+($impuesto/100)),2);
                $impuesto_porc = number_format(($total-($total/(1+($impuesto/100)))),2);
                $total         = number_format($total,2);
            }else{
                $impuesto      = number_format(0,2);
                $base          = number_format(0,2);
                $impuesto_porc = number_format(0,2);
                $total         = number_format(0,2);
            }

            $printer->text(self::addSpacesNumber('Base:', 38) . self::addSpacesNumber($base, 10) . "\n");
            $printer->text(self::addSpacesNumber('Impuesto '.$impuesto.'%:', 38) . self::addSpacesNumber($impuesto_porc, 10) . "\n");

            $descuento = $req['descuento'];
            if($descuento){
                $printer->setTextSize(1,1);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);

                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($desc_tipo == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($desc_tipo == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }
                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
                $printer->setTextSize(1,1);
            }else{
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);
            }

            if($req['cortesia'] == 1){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("\n");
                $printer->text("******************************************"."\n");
                $printer->text("**************** CORTESIA ****************"."\n");
                $printer->text("******************************************"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }else{
                $pago_tipo = $req['pago_tipo'];
                if($pago_tipo){
                    $printer->text("\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->selectPrintMode();
                    $printer->setEmphasis(false);
                    //$printer->text('Tipo de Pago: '."\n");
                    $efectivo = floatval($pago_tipo['efectivo']);
                    $tarjeta = floatval($pago_tipo['tarjeta']);
                    $tipo_pago_text = 'Tipo de Pago';
                    $efectivo_text = '';
                    $tarjeta_text = '';
                    if($efectivo>0){
                        $efectivo_text = 'Efectivo: '.$efectivo;
                        //$printer->text('Efectivo: '.self::sanear_string($efectivo)."\n");
                    }
                    if($tarjeta>0){
                        $tarjeta_text = 'Tarjeta: '.$tarjeta;
                        //$printer->text('Tarjeta: '.self::sanear_string($tarjeta)."\n");
                    }
                    $propina = floatval($pago_tipo['propina']);
                    $propina_efec = floatval($pago_tipo['propina_efec']);
                    $propina_tarj = floatval($pago_tipo['propina_tarj']);
                    $propina_text = '';
                    $propina_efec_text = '';
                    $propina_tarj_text = '';
                    if($propina == 1){
                        $propina_text = 'Propina';
                        //$printer->text("\n");
                        //$printer->text('Propina: '."\n");
                        if($propina_efec>0){
                            $propina_efec_text = 'Efectivo: '.$propina_efec;
                            //$printer->text('Efectivo: '.self::sanear_string($propina_efec)."\n");
                        }
                        if($propina_tarj>0){
                            $propina_tarj_text = 'Tarjeta: '.$propina_tarj;
                            //$printer->text('Tarjeta: '.self::sanear_string($propina_tarj)."\n");
                        }
                    }
                    $printer->setEmphasis(true);
                    $printer->text(self::addSpacesString($tipo_pago_text, 24) . self::addSpacesString($propina_text, 24) . "\n");
                    $printer->setEmphasis(false);
                    $printer->text(self::addSpacesString($efectivo_text, 24) . self::addSpacesString($propina_efec_text, 24) . "\n");
                    $printer->text(self::addSpacesString($tarjeta_text, 24) . self::addSpacesString($propina_tarj_text, 24) . "\n");
                    $tipo_pago = floatval($pago_tipo['pago_tipo']);
                    $pago_con = floatval($pago_tipo['pago_con']);
                    $vuelto = $pago_con-floatval($efectivo+$propina_efec);
                    $tarjeta_id_text = $pago_tipo['tarjeta_id_text'];
                    if($tipo_pago == 1){
                        $printer->text("\n");
                        $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                        $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                    }else if($tipo_pago == 2){
                        $printer->text("\n");
                        $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                    }else if($tipo_pago == 3){
                        $printer->text("\n");
                        $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                        $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                        $printer->text("\n");
                        $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                    }
                }
            }

            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            $printer->text("\n");
            $printer->text('Cajero: ' . $req['p_c_nombre'] . "\n");

            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Gracias por su compra!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Este documento no posee ningun valor fiscal!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json($req);

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    public function documento_fiscal(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            $cuenta = $req['cuenta'];
            $json_fact_elect = $req['json_fact_elect'];
            $html = $json_fact_elect['html'];

            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(self::sanear_string($cuenta['e_razon_social'])."\n");
            $printer->text(self::sanear_string($cuenta['l_direccion'])."\n");
            $printer->text('Telf: '.$cuenta['l_telefono'].' / RUC: '. $cuenta['e_codigo'] ."\n");
            $printer->text('Fecha: '.$cuenta['p_fecha']."\n");
            $printer->text("\n");
            $printer->setEmphasis(true);
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->text("ANULADO"."\n");
                $printer->text("\n");
            }
            $printer->text(self::sanear_string($json_fact_elect['tipo_text']." ".$json_fact_elect['codigo'])."\n");
            $printer->text("\n");
            $printer->text('Cliente: '.self::sanear_string($cuenta['p_cliente'])."\n");
            $printer->text("\n");
            $printer->setJustification();
            if($json_fact_elect['cliente_numero_de_documento']){
                $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                $printer->text($json_fact_elect['cliente_numero_de_documento']."\n");
                if(isset($json_fact_elect['cliente_direccion'])){
                    $printer->text(self::sanear_string($json_fact_elect['cliente_direccion'])."\n");
                }
                $printer->text("\n");
            }else{
                if($json_fact_elect['cliente_denominacion']){
                    $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                }
            }

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Producto', 22) . self::addSpacesNumber('Cant.', 8) . self::addSpacesNumber('Precio', 8) . self::addSpacesNumber('Total', 10) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            $total = 0;

            $detalles = $cuenta['detalles'];
            foreach ($detalles as $item) {
                //Current item ROW 1

                $producto_lines = str_split(self::sanear_string($item['producto']),22);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,22);
                }
                
                $cantidad = str_split(number_format(round(floatval($item['cantidad']),2),2),8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesNumber($l,8);
                }
            
                $precio = str_split(number_format(round(floatval($item['precio']),2),2),8);
                foreach ($precio as $k => $l) {
                    $l = trim($l);
                    $precio[$k] = self::addSpacesNumber($l,8);
                }
                
                $total_mult = $item['cantidad'] * $item['precio'];
                $total = $total+$total_mult;
                $total_str = str_split(number_format(round(floatval($total_mult),2),2),10);
                foreach ($total_str as $k => $l) {
                    $l = trim($l);
                    $total_str[$k] = self::addSpacesNumber($l,10);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($producto_lines);
                $temp[] = count($cantidad);
                $temp[] = count($precio);
                $temp[] = count($total_str);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($precio[$i])) {
                        $line .= ($precio[$i]);
                    }
                    if (isset($total_str[$i])) {
                        $line .= ($total_str[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }

                //Para una segunda fila
                if($item['observacion']){
                    $stringObserv = '';
                    if(is_array($item['observacion'])){
                        foreach ($item['observacion'] as $keyObserv => $valueObserv) {
                            if(!empty(trim($valueObserv['descripcion']))){
                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                            }
                        }
                        if(empty(trim($stringObserv))){
                            $stringObserv = null;
                        }else{
                            $stringObserv = substr(($stringObserv),0,-2);
                        }
                    }else{
                        if(!empty(trim($item['observacion']))){
                            $stringObserv = $item['observacion'];
                        }else{
                            $stringObserv = null;
                        }
                    }
                    if(!empty(trim($stringObserv))){
                        $observacion_lines = str_split('- '.$stringObserv, 19);
                        foreach ($observacion_lines as $k => $l) {
                            $l = trim($l);
                            $observacion_lines[$k] = self::addSpacesString($l, 19);
                        }
                    
                        $counter = 0;
                        $temp = [];
                        $temp[] = count($observacion_lines);
                        $counter = max($temp);
                    
                        for ($i = 0; $i < $counter; $i++) {
                            $line = '';
                            if (isset($observacion_lines[$i])) {
                                $line .= ($observacion_lines[$i]);
                            }
                    
                            $printer->text(self::addSpacesString('',4).$line . "\n");
                        }
                    }
                }
                $counter = 0;
            }
            $printer->selectPrintMode();
            
            $impuesto      = round(floatval($cuenta['impuesto']),2);
            $base          = number_format($total/(1+($impuesto/100)),2);
            $impuesto_porc = number_format(($total-($total/(1+($impuesto/100)))),2);
            $total         = number_format($total,2);

            $printer->text(self::addSpacesNumber('Base:', 38) . self::addSpacesNumber($base, 10) . "\n");
            $printer->text(self::addSpacesNumber('Impuesto '.$impuesto.'%:', 38) . self::addSpacesNumber($impuesto_porc, 10) . "\n");

            $descuento = $cuenta['descuento'];
            if($descuento){
                $printer->setTextSize(1,1);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);

                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($desc_tipo == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($desc_tipo == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }
                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
                $printer->setTextSize(1,1);
            }else{
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);
            }

            $pago_tipo = $cuenta['pago_tipo'];
            if($pago_tipo){
                $printer->text("\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->selectPrintMode();
                $printer->setEmphasis(false);
                //$printer->text('Tipo de Pago: '."\n");
                $efectivo = floatval($pago_tipo['efectivo']);
                $tarjeta = floatval($pago_tipo['tarjeta']);
                $tipo_pago_text = 'Tipo de Pago';
                $efectivo_text = '';
                $tarjeta_text = '';
                if($efectivo>0){
                    $efectivo_text = 'Efectivo: '.$efectivo;
                    //$printer->text('Efectivo: '.self::sanear_string($efectivo)."\n");
                }
                if($tarjeta>0){
                    $tarjeta_text = 'Tarjeta: '.$tarjeta;
                    //$printer->text('Tarjeta: '.self::sanear_string($tarjeta)."\n");
                }
                $propina = floatval($pago_tipo['propina']);
                $propina_efec = floatval($pago_tipo['propina_efec']);
                $propina_tarj = floatval($pago_tipo['propina_tarj']);
                $propina_text = '';
                $propina_efec_text = '';
                $propina_tarj_text = '';
                if($propina == 1){
                    $propina_text = 'Propina';
                    //$printer->text("\n");
                    //$printer->text('Propina: '."\n");
                    if($propina_efec>0){
                        $propina_efec_text = 'Efectivo: '.$propina_efec;
                        //$printer->text('Efectivo: '.self::sanear_string($propina_efec)."\n");
                    }
                    if($propina_tarj>0){
                        $propina_tarj_text = 'Tarjeta: '.$propina_tarj;
                        //$printer->text('Tarjeta: '.self::sanear_string($propina_tarj)."\n");
                    }
                }
                $printer->setEmphasis(true);
                $printer->text(self::addSpacesString($tipo_pago_text, 24) . self::addSpacesString($propina_text, 24) . "\n");
                $printer->setEmphasis(false);
                $printer->text(self::addSpacesString($efectivo_text, 24) . self::addSpacesString($propina_efec_text, 24) . "\n");
                $printer->text(self::addSpacesString($tarjeta_text, 24) . self::addSpacesString($propina_tarj_text, 24) . "\n");
                $tipo_pago = floatval($pago_tipo['pago_tipo']);
                $pago_con = floatval($pago_tipo['pago_con']);
                $vuelto = $pago_con-floatval($efectivo+$propina_efec);
                $tarjeta_id_text = $pago_tipo['tarjeta_id_text'];
                if($tipo_pago == 1){
                    $printer->text("\n");
                    $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                    $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                }else if($tipo_pago == 2){
                    $printer->text("\n");
                    $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                }else if($tipo_pago == 3){
                    $printer->text("\n");
                    $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                    $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                    $printer->text("\n");
                    $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                }
            }

            $printer->text("\n");
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            if($json_fact_elect['cliente_numero_de_documento']){
                $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                $printer->text("\n");
            }
            //self::sanear_string($cuenta['p_c_nombre'])
            $printer->text('Cajero: ' . self::sanear_string($cuenta['p_c_nombre']) . "\n");

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('¡ Gracias por su compra !' . "\n");
            $printer->text("\n");
            $printer->text('Consulte su documento en el portal web:' . "\n");
            $printer->text('nubefact.com/20601363029' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json(true);

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    public function comanda(Request $req) {
        try{
            $comandas = [];
            $impresoras = $req['impresoras'];
            $detalles = $req['detalles'];
            if(!empty($impresoras)){
                foreach ($impresoras as $key => $value) {
                    $impresora['ip'] = $value['ip'];
                    $items = [];
                    if(!empty($value['categorias'])){
                        foreach ($value['categorias'] as $keyCat => $valueCat) {
                            if(!empty($detalles)){
                                foreach ($detalles as $keyDet => $valueDet) {
                                    if( intval($valueCat) == intval($valueDet['categoria']) ){
                                        unset($valueDet['categoria']);
                                        unset($valueDet['date_created']);
                                        unset($valueDet['date_now']);
                                        unset($valueDet['dates_diff']);
                                        unset($valueDet['estado']);
                                        unset($valueDet['estado_eliminado']);
                                        unset($valueDet['fecha_atencion_plato']);
                                        unset($valueDet['fecha_despacho_plato']);
                                        unset($valueDet['fecha_registro']);
                                        unset($valueDet['fk_id_producto']);
                                        unset($valueDet['id']);
                                        unset($valueDet['id_users']);
                                        unset($valueDet['observ_eliminado']);
                                        unset($valueDet['observacion2']);
                                        unset($valueDet['platos_estado']);
                                        unset($valueDet['precio']);
                                        
                                        $stringObserv = '';
                                        if(is_array($valueDet['observacion'])){
                                            foreach ($valueDet['observacion'] as $keyObserv => $valueObserv) {
                                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                                            }
                                            if(empty(trim($stringObserv))){
                                                $valueDet['observacion'] = null;
                                            }else{
                                                $valueDet['observacion'] = substr(($stringObserv),0,-2);
                                            }
                                        }else{
                                            if(!empty(trim($valueDet['observacion']))){
                                                $stringObserv = $valueDet['observacion'];
                                            }else{
                                                $valueDet['observacion'] = null;
                                            }
                                        }
                                        array_push($items,$valueDet);
                                    }
                                }
                            }
                        }
                        if(!empty($items)){
                            $impresora['items'] = $items;
                            array_push($comandas,$impresora);
                        }
                    }
                }
            }
            if(!empty($comandas)){
                foreach ($comandas as $key => $value) {
                    self::imprimirComandas($req,$value['items'],$value['ip']);
                }
            }
            //return Response::json($comandas);
            
        }catch(Exception $e){
            return Response::json($e);
        }
    }
    function imprimirComandas($req,$detalles,$ip) {
        try{
            $connector = new NetworkPrintConnector($ip);
            //$profile = SimpleCapabilityProfile::getInstance();
            //$profile = CapabilityProfile::load("CP-850");
            //$profile = CustomCapabilityProfile::getInstance();
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("\n");
            $printer->text("\n");
            $printer->text("\n");
            $printer->text("\n");
            $printer->setTextSize(2,2);
            $printer->text('*** RECOJO EN TIENDA ***');
            $printer->setTextSize(1,1);
            $printer->text("\n");
            $printer->setEmphasis();
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("\n");
            $printer->text('Usuario: '.$req['p_nombre']."\n");
            $printer->text('Cliente: '.$req['p_cliente']."\n");
            $printer->text('Fecha: '.$req['p_fecha']."\n");
            $printer->setEmphasis(true);
            $printer->text("\n");
            $printer->setJustification();

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Cant.', 8) . self::addSpacesString('Producto', 40) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            foreach ($detalles as $item) {
                //Current item ROW 1
                $printer->text("\n");
                
                $cantidad = str_split($item['cantidad'],8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesString($l,8);
                }

                $producto_lines = str_split(self::sanear_string($item['producto']),40);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,40);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($cantidad);
                $temp[] = count($producto_lines);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }

                //Para una segunda fila
                if($item['observacion']){
                    $observacion_lines = str_split('- '.self::sanear_string($item['observacion']), 36);
                    foreach ($observacion_lines as $k => $l) {
                        $l = trim($l);
                        $observacion_lines[$k] = self::addSpacesString($l, 36);
                    }
                
                    $counter = 0;
                    $temp = [];
                    $temp[] = count($observacion_lines);
                    $counter = max($temp);
                
                    for ($i = 0; $i < $counter; $i++) {
                        $line = '';
                        if (isset($observacion_lines[$i])) {
                            $line .= ($observacion_lines[$i]);
                        }
                
                        $printer->text(self::addSpacesString('',12).$line . "\n");
                    }
                }
                $printer->text("\n");
                //Fin de la segunda fila
            }

            $printer->text("\n");

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->setTextSize(2,2);
            $printer->text('*** RECOJO EN TIENDA ***');

            $printer->selectPrintMode();
            $printer->text("\n");

            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json('$profile');

        }catch(Exception $e){
            return Response::json($e);
        }
    }

    //delivery
    public function comandaDelivery(Request $req) {
        try{
            $comandas = [];
            $impresoras = $req['impresoras'];
            $detalles = $req['detalles'];
            if(!empty($impresoras)){
                foreach ($impresoras as $key => $value) {
                    $impresora['ip'] = $value['ip'];
                    $items = [];
                    if(!empty($value['categorias'])){
                        foreach ($value['categorias'] as $keyCat => $valueCat) {
                            if(!empty($detalles)){
                                foreach ($detalles as $keyDet => $valueDet) {
                                    if( intval($valueCat) == intval($valueDet['categoria']) ){
                                        unset($valueDet['categoria']);
                                        unset($valueDet['date_created']);
                                        unset($valueDet['date_now']);
                                        unset($valueDet['dates_diff']);
                                        unset($valueDet['estado']);
                                        unset($valueDet['estado_eliminado']);
                                        unset($valueDet['fecha_atencion_plato']);
                                        unset($valueDet['fecha_despacho_plato']);
                                        unset($valueDet['fecha_registro']);
                                        unset($valueDet['fk_id_producto']);
                                        unset($valueDet['id']);
                                        unset($valueDet['id_users']);
                                        unset($valueDet['observ_eliminado']);
                                        unset($valueDet['observacion2']);
                                        unset($valueDet['platos_estado']);
                                        unset($valueDet['precio']);
                                        
                                        $stringObserv = '';
                                        if(is_array($valueDet['observacion'])){
                                            foreach ($valueDet['observacion'] as $keyObserv => $valueObserv) {
                                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                                            }
                                            if(empty(trim($stringObserv))){
                                                $valueDet['observacion'] = null;
                                            }else{
                                                $valueDet['observacion'] = substr(($stringObserv),0,-2);
                                            }
                                        }else{
                                            if(!empty(trim($valueDet['observacion']))){
                                                $stringObserv = $valueDet['observacion'];
                                            }else{
                                                $valueDet['observacion'] = null;
                                            }
                                        }

                                        array_push($items,$valueDet);
                                    }
                                }
                            }
                        }
                        if(!empty($items)){
                            $impresora['items'] = $items;
                            array_push($comandas,$impresora);
                        }
                    }
                }
            }
            if(!empty($comandas)){
                foreach ($comandas as $key => $value) {
                    self::imprimirComandasDelivery($req,$value['items'],$value['ip']);
                }
            }
            
        }catch(Exception $e){
            return Response::json($e);
        }
    }
    function imprimirComandasDelivery($req,$detalles,$ip) {
        try{
            $connector = new NetworkPrintConnector($ip);
            //$profile = SimpleCapabilityProfile::getInstance();
            //$profile = CapabilityProfile::load("CP-850");
            //$profile = CustomCapabilityProfile::getInstance();
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text("\n");
            $printer->text("\n");
            $printer->text("\n");
            $printer->text("\n");
            $printer->setTextSize(2,2);
            $printer->text('* * * DELIVERY * * *');
            $printer->setTextSize(1,1);
            $printer->text("\n");
            $printer->setEmphasis();
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("\n");
            $printer->text('Usuario: '.$req['p_nombre']."\n");
            $printer->text('Cliente: '.$req['llevar_cliente'] . ' / '.$req['p_cliente']['nombres']."\n");
            $printer->text('Direccion: '.$req['p_cliente']['direccion']."\n");
            $printer->text('Fecha: '.$req['p_fecha']."\n");
            $printer->setEmphasis(true);
            $printer->text("\n");
            $printer->setJustification();

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Cant.', 8) . self::addSpacesString('Producto', 40) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            foreach ($detalles as $item) {
                //Current item ROW 1
                $printer->text("\n");
                
                $cantidad = str_split($item['cantidad'],8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesString($l,8);
                }

                $producto_lines = str_split(self::sanear_string($item['producto']),40);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,40);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($cantidad);
                $temp[] = count($producto_lines);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }

                //Para una segunda fila
                if($item['observacion']){
                    $observacion_lines = str_split('- '.self::sanear_string($item['observacion']), 36);
                    foreach ($observacion_lines as $k => $l) {
                        $l = trim($l);
                        $observacion_lines[$k] = self::addSpacesString($l, 36);
                    }
                
                    $counter = 0;
                    $temp = [];
                    $temp[] = count($observacion_lines);
                    $counter = max($temp);
                
                    for ($i = 0; $i < $counter; $i++) {
                        $line = '';
                        if (isset($observacion_lines[$i])) {
                            $line .= ($observacion_lines[$i]);
                        }
                
                        $printer->text(self::addSpacesString('',12).$line . "\n");
                    }
                }
                $printer->text("\n");
                //Fin de la segunda fila
            }
            $printer->selectPrintMode();
            $printer->text("\n");

            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json('$profile');

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    public function motorizadoDelivery(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text($req['e_razon_social']."\n");
            $printer->text($req['l_direccion']."\n");
            $printer->text('Telf: '.$req['l_telefono'].' / RUC: '. $req['e_codigo'] ."\n");
            $printer->text('Fecha: '.$req['p_fecha']."\n");
            $printer->text("\n");
            $printer->setEmphasis(true);
            $printer->setJustification();
            $printer->text('Cliente: '.$req['llevar_cliente'] . ' / '.$req['p_cliente']['nombres']."\n");
            $printer->text('Direccion: '.$req['p_cliente']['direccion']."\n");
            if($req['p_cliente']['telefono']){
                $printer->text('Telefono: '.$req['p_cliente']['telefono']."\n");
            }
            if($req['p_cliente']['contacto']){
                $printer->text('Contacto: '.$req['p_cliente']['contacto']."\n");
            }
            //$printer->text('Cliente: '.self::sanear_string($req['p_cliente'])."\n");
            $printer->text("\n");
            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            //self::sanear_string()

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Producto', 22) . self::addSpacesNumber('Cant.', 8) . self::addSpacesNumber('Precio', 8) . self::addSpacesNumber('Total', 10) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            $total = 0;

            $detalles = $req['detalles'];
            foreach ($detalles as $item) {
                //Current item ROW 1

                $producto_lines = str_split(self::sanear_string($item['producto']),22);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,22);
                }

                $cantidad = str_split(number_format(round(floatval($item['cantidad']),2),2),8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesNumber($l,8);
                }
            
                $precio = str_split(number_format(round(floatval($item['precio']),2),2),8);
                foreach ($precio as $k => $l) {
                    $l = trim($l);
                    $precio[$k] = self::addSpacesNumber($l,8);
                }
                
                $total_mult = $item['cantidad'] * $item['precio'];
                $total = $total+$total_mult;
                $total_str = str_split(number_format(round(floatval($total_mult),2),2),10);
                foreach ($total_str as $k => $l) {
                    $l = trim($l);
                    $total_str[$k] = self::addSpacesNumber($l,10);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($producto_lines);
                $temp[] = count($cantidad);
                $temp[] = count($precio);
                $temp[] = count($total_str);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($precio[$i])) {
                        $line .= ($precio[$i]);
                    }
                    if (isset($total_str[$i])) {
                        $line .= ($total_str[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }


                //Para una segunda fila
                if($item['observacion']){
                    $stringObserv = '';
                    if(is_array($item['observacion'])){
                        foreach ($item['observacion'] as $keyObserv => $valueObserv) {
                            if(!empty(trim($valueObserv['descripcion']))){
                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                            }
                        }
                        if(empty(trim($stringObserv))){
                            $stringObserv = null;
                        }else{
                            $stringObserv = substr(($stringObserv),0,-2);
                        }
                    }else{
                        if(!empty(trim($item['observacion']))){
                            $stringObserv = $item['observacion'];
                        }else{
                            $stringObserv = null;
                        }
                    }
                    if(!empty(trim($stringObserv))){
                        $observacion_lines = str_split('- '.$stringObserv, 19);
                        foreach ($observacion_lines as $k => $l) {
                            $l = trim($l);
                            $observacion_lines[$k] = self::addSpacesString($l, 19);
                        }
                    
                        $counter = 0;
                        $temp = [];
                        $temp[] = count($observacion_lines);
                        $counter = max($temp);
                    
                        for ($i = 0; $i < $counter; $i++) {
                            $line = '';
                            if (isset($observacion_lines[$i])) {
                                $line .= ($observacion_lines[$i]);
                            }
                    
                            $printer->text(self::addSpacesString('',4).$line . "\n");
                        }
                    }
                }
            }
            $printer->selectPrintMode();
            
            $impuesto      = round(floatval($req['impuesto']),2);
            $base          = number_format($total/(1+($impuesto/100)),2);
            $impuesto_porc = number_format(($total-($total/(1+($impuesto/100)))),2);
            $total         = number_format($total,2);

            $printer->text(self::addSpacesNumber('Base:', 38) . self::addSpacesNumber($base, 10) . "\n");
            $printer->text(self::addSpacesNumber('Impuesto '.$impuesto.'%:', 38) . self::addSpacesNumber($impuesto_porc, 10) . "\n");
            
            $descuento = $req['descuento'];
            if($descuento){
                $printer->setTextSize(1,1);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);

                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($desc_tipo == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($desc_tipo == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }
                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
                $printer->setTextSize(1,1);
            }else{
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);
            }

            if($req['delivery']){
                $printer->setEmphasis(true);
                $printer->text('Metodo de pago: '."\n");
                $printer->setEmphasis(false);
                if($req['delivery']['fk_id_pago_tipo'] == 1){
                    $printer->text('   - Efectivo: '.$req['delivery']['efectivo']."\n");
                    $printer->text('   - Pagara con: '.$req['delivery']['pago_con']."\n");
                    $printer->text('   - Vuelto: '.($req['delivery']['pago_con']-$req['delivery']['efectivo'])."\n");
                }else if($req['delivery']['fk_id_pago_tipo'] == 2){
                    $printer->text('   - Tarjeta: '.$req['delivery']['tarjeta']."\n");
                }else{
                    $printer->text('   - Efectivo: '.$req['delivery']['efectivo']."\n");
                    $printer->text('   - Pagara con: '.$req['delivery']['pago_con']."\n");
                    $printer->text('   - Vuelto: '.($req['delivery']['pago_con']-$req['delivery']['efectivo'])."\n");
                    $printer->text('   - Tarjeta: '.$req['delivery']['tarjeta']."\n");
                }
            }

            if($req['motorizado']){
                $printer->text("\n");
                $printer->setEmphasis(true);
                $printer->text('Motorizado: '."\n");
                $printer->setEmphasis(false);
                $printer->text($req['motorizado']['doc_tipo'] . ': ' . $req['motorizado']['numero_doc'] . ' - ' . $req['motorizado']['text'] . "\n");
            }

            if($descuento){
                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($descuento['desc_tipo'] == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($descuento['desc_tipo'] == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }

                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
            }

            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            if($req['cortesia'] == 1){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("\n");
                $printer->text("******************************************"."\n");
                $printer->text("**************** CORTESIA ****************"."\n");
                $printer->text("******************************************"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Gracias por su compra!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Este documento no posee ningun valor fiscal!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json($req);

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    public function cuentaRapidaDelivery(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text($req['e_razon_social']."\n");
            $printer->text($req['l_direccion']."\n");
            $printer->text('Telf: '.$req['l_telefono'].' / RUC: '. $req['e_codigo'] ."\n");
            $printer->text('Fecha: '.$req['p_fecha']."\n");
            $printer->text("\n");
            $printer->setEmphasis(true);
            $printer->setJustification();
            $printer->text('Cliente: '.$req['llevar_cliente'] . ' / '.$req['p_cliente']['nombres']."\n");
            $printer->text('Direccion: '.$req['p_cliente']['direccion']."\n");
            if($req['p_cliente']['telefono']){
                $printer->text('Telefono: '.$req['p_cliente']['telefono']."\n");
            }
            if($req['p_cliente']['contacto']){
                $printer->text('Contacto: '.$req['p_cliente']['contacto']."\n");
            }
            $printer->setJustification();
            if($req['p_estado'] == 0){
                $printer->text("\n");
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Producto', 22) . self::addSpacesNumber('Cant.', 8) . self::addSpacesNumber('Precio', 8) . self::addSpacesNumber('Total', 10) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            $total = 0;

            $detalles = $req['detalles'];
            foreach ($detalles as $item) {
                //Current item ROW 1

                $producto_lines = str_split(self::sanear_string($item['producto']),22);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,22);
                }
                
                $cantidad = str_split(number_format(round(floatval($item['cantidad']),2),2),8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesNumber($l,8);
                }
            
                $precio = str_split(number_format(round(floatval($item['precio']),2),2),8);
                foreach ($precio as $k => $l) {
                    $l = trim($l);
                    $precio[$k] = self::addSpacesNumber($l,8);
                }
                
                $total_mult = $item['cantidad'] * $item['precio'];
                $total = $total+$total_mult;
                $total_str = str_split(number_format(round(floatval($total_mult),2),2),10);
                foreach ($total_str as $k => $l) {
                    $l = trim($l);
                    $total_str[$k] = self::addSpacesNumber($l,10);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($producto_lines);
                $temp[] = count($cantidad);
                $temp[] = count($precio);
                $temp[] = count($total_str);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($precio[$i])) {
                        $line .= ($precio[$i]);
                    }
                    if (isset($total_str[$i])) {
                        $line .= ($total_str[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }


                //Para una segunda fila
                if($item['observacion']){
                    $stringObserv = '';
                    if(is_array($item['observacion'])){
                        foreach ($item['observacion'] as $keyObserv => $valueObserv) {
                            if(!empty(trim($valueObserv['descripcion']))){
                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                            }
                        }
                        if(empty(trim($stringObserv))){
                            $stringObserv = null;
                        }else{
                            $stringObserv = substr(($stringObserv),0,-2);
                        }
                    }else{
                        if(!empty(trim($item['observacion']))){
                            $stringObserv = $item['observacion'];
                        }else{
                            $stringObserv = null;
                        }
                    }
                    if(!empty(trim($stringObserv))){
                        $observacion_lines = str_split('- '.$stringObserv, 19);
                        foreach ($observacion_lines as $k => $l) {
                            $l = trim($l);
                            $observacion_lines[$k] = self::addSpacesString($l, 19);
                        }
                    
                        $counter = 0;
                        $temp = [];
                        $temp[] = count($observacion_lines);
                        $counter = max($temp);
                    
                        for ($i = 0; $i < $counter; $i++) {
                            $line = '';
                            if (isset($observacion_lines[$i])) {
                                $line .= ($observacion_lines[$i]);
                            }
                    
                            $printer->text(self::addSpacesString('',4).$line . "\n");
                        }
                    }
                }
            }
            $printer->selectPrintMode();
            
            if($req['cortesia']==0){
                $impuesto      = round(floatval($req['impuesto']),2);
                $base          = number_format($total/(1+($impuesto/100)),2);
                $impuesto_porc = number_format(($total-($total/(1+($impuesto/100)))),2);
                $total         = number_format($total,2);
            }else{
            
                $impuesto      = number_format(0,2);
                $base          = number_format(0,2);
                $impuesto_porc = number_format(0,2);
                $total         = number_format(0,2);
            }

            $printer->text(self::addSpacesNumber('Base:', 38) . self::addSpacesNumber($base, 10) . "\n");
            $printer->text(self::addSpacesNumber('Impuesto '.$impuesto.'%:', 38) . self::addSpacesNumber($impuesto_porc, 10) . "\n");

            $descuento = $req['descuento'];
            if($descuento){
                $printer->setTextSize(1,1);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);

                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($desc_tipo == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($desc_tipo == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }
                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
                $printer->setTextSize(1,1);
            }else{
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);
            }

            if($req['cortesia'] == 1){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("\n");
                $printer->text("******************************************"."\n");
                $printer->text("**************** CORTESIA ****************"."\n");
                $printer->text("******************************************"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }else{
                $pago_tipo = $req['pago_tipo'];
                if($pago_tipo){
                    $printer->text("\n");
                    $printer->setJustification(Printer::JUSTIFY_LEFT);
                    $printer->selectPrintMode();
                    $printer->setEmphasis(false);
                    //$printer->text('Tipo de Pago: '."\n");
                    $efectivo = floatval($pago_tipo['efectivo']);
                    $tarjeta = floatval($pago_tipo['tarjeta']);
                    $tipo_pago_text = 'Tipo de Pago';
                    $efectivo_text = '';
                    $tarjeta_text = '';
                    if($efectivo>0){
                        $efectivo_text = 'Efectivo: '.$efectivo;
                        //$printer->text('Efectivo: '.self::sanear_string($efectivo)."\n");
                    }
                    if($tarjeta>0){
                        $tarjeta_text = 'Tarjeta: '.$tarjeta;
                        //$printer->text('Tarjeta: '.self::sanear_string($tarjeta)."\n");
                    }
                    $propina = floatval($pago_tipo['propina']);
                    $propina_efec = floatval($pago_tipo['propina_efec']);
                    $propina_tarj = floatval($pago_tipo['propina_tarj']);
                    $propina_text = '';
                    $propina_efec_text = '';
                    $propina_tarj_text = '';
                    if($propina == 1){
                        $propina_text = 'Propina';
                        //$printer->text("\n");
                        //$printer->text('Propina: '."\n");
                        if($propina_efec>0){
                            $propina_efec_text = 'Efectivo: '.$propina_efec;
                            //$printer->text('Efectivo: '.self::sanear_string($propina_efec)."\n");
                        }
                        if($propina_tarj>0){
                            $propina_tarj_text = 'Tarjeta: '.$propina_tarj;
                            //$printer->text('Tarjeta: '.self::sanear_string($propina_tarj)."\n");
                        }
                    }
                    $printer->setEmphasis(true);
                    $printer->text(self::addSpacesString($tipo_pago_text, 24) . self::addSpacesString($propina_text, 24) . "\n");
                    $printer->setEmphasis(false);
                    $printer->text(self::addSpacesString($efectivo_text, 24) . self::addSpacesString($propina_efec_text, 24) . "\n");
                    $printer->text(self::addSpacesString($tarjeta_text, 24) . self::addSpacesString($propina_tarj_text, 24) . "\n");
                    $tipo_pago = floatval($pago_tipo['pago_tipo']);
                    $pago_con = floatval($pago_tipo['pago_con']);
                    $vuelto = $pago_con-floatval($efectivo+$propina_efec);
                    $tarjeta_id_text = $pago_tipo['tarjeta_id_text'];
                    if($tipo_pago == 1){
                        $printer->text("\n");
                        $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                        $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                    }else if($tipo_pago == 2){
                        $printer->text("\n");
                        $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                    }else if($tipo_pago == 3){
                        $printer->text("\n");
                        $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                        $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                        $printer->text("\n");
                        $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                    }
                }
            }

            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            $printer->text("\n");
            $printer->text('Cajero: ' . $req['p_c_nombre'] . "\n");

            if($req['p_estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Gracias por su compra!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('Este documento no posee ningun valor fiscal!' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json($req);

        }catch(Exception $e){
            return Response::json($e);
        }
    }
    public function documento_fiscalDelivery(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $profile = DefaultCapabilityProfile::getInstance();
            $printer = new Printer($connector, $profile);
            $verbose = false;

            $cuenta = $req['cuenta'];
            $json_fact_elect = $req['json_fact_elect'];
            $html = $json_fact_elect['html'];

            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(self::sanear_string($cuenta['e_razon_social'])."\n");
            $printer->text(self::sanear_string($cuenta['l_direccion'])."\n");
            $printer->text('Telf: '.$cuenta['l_telefono'].' / RUC: '. $cuenta['e_codigo'] ."\n");
            $printer->text('Fecha: '.$cuenta['p_fecha']."\n");
            $printer->text("\n");
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->text("ANULADO"."\n");
                $printer->text("\n");
            }
            $printer->setJustification();
            $printer->setEmphasis(false);
            $printer->text('Cliente: '.$req['cuenta']['llevar_cliente'] . ' / '.$req['cuenta']['p_cliente']['nombres']."\n");
            $printer->text('Direccion: '.$req['cuenta']['p_cliente']['direccion']."\n");
            if($req['cuenta']['p_cliente']['telefono']){
                $printer->text('Telefono: '.$req['cuenta']['p_cliente']['telefono']."\n");
            }
            if($req['cuenta']['p_cliente']['contacto']){
                $printer->text('Contacto: '.$req['cuenta']['p_cliente']['contacto']."\n");
            }
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text(self::sanear_string($json_fact_elect['tipo_text']." ".$json_fact_elect['codigo'])."\n");
            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            if($json_fact_elect['cliente_numero_de_documento']){
                $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                $printer->text($json_fact_elect['cliente_numero_de_documento']."\n");
                if(isset($json_fact_elect['cliente_direccion'])){
                    $printer->text(self::sanear_string($json_fact_elect['cliente_direccion'])."\n");
                }
                $printer->text("\n");
            }else{
                if($json_fact_elect['cliente_denominacion']){
                    $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                }
            }

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->selectPrintMode(Printer::MODE_UNDERLINE);
            $printer->setEmphasis(true);
            $printer->text(self::addSpacesString('Producto', 22) . self::addSpacesNumber('Cant.', 8) . self::addSpacesNumber('Precio', 8) . self::addSpacesNumber('Total', 10) . "\n");
            $printer->setEmphasis(false);
            $printer->selectPrintMode();

            $total = 0;

            $detalles = $cuenta['detalles'];
            foreach ($detalles as $item) {
                //Current item ROW 1

                $producto_lines = str_split(self::sanear_string($item['producto']),22);
                foreach ($producto_lines as $k => $l) {
                    $l = trim($l);
                    $producto_lines[$k] = self::addSpacesString($l,22);
                }
                
                $cantidad = str_split(number_format(round(floatval($item['cantidad']),2),2),8);
                foreach ($cantidad as $k => $l) {
                    $l = trim($l);
                    $cantidad[$k] = self::addSpacesNumber($l,8);
                }
            
                $precio = str_split(number_format(round(floatval($item['precio']),2),2),8);
                foreach ($precio as $k => $l) {
                    $l = trim($l);
                    $precio[$k] = self::addSpacesNumber($l,8);
                }
                
                $total_mult = $item['cantidad'] * $item['precio'];
                $total = $total+$total_mult;
                $total_str = str_split(number_format(round(floatval($total_mult),2),2),10);
                foreach ($total_str as $k => $l) {
                    $l = trim($l);
                    $total_str[$k] = self::addSpacesNumber($l,10);
                }
            
                $counter = 0;
                $temp = [];
                $temp[] = count($producto_lines);
                $temp[] = count($cantidad);
                $temp[] = count($precio);
                $temp[] = count($total_str);
                $counter = max($temp);
            
                for ($i = 0; $i < $counter; $i++) {
                    $line = '';
                    if (isset($producto_lines[$i])) {
                        $line .= ($producto_lines[$i]);
                    }
                    if (isset($cantidad[$i])) {
                        $line .= ($cantidad[$i]);
                    }
                    if (isset($precio[$i])) {
                        $line .= ($precio[$i]);
                    }
                    if (isset($total_str[$i])) {
                        $line .= ($total_str[$i]);
                    }
                    $printer->text(self::addSpacesString($line, 48) . "\n");
                }

                //Para una segunda fila
                if($item['observacion']){
                    $stringObserv = '';
                    if(is_array($item['observacion'])){
                        foreach ($item['observacion'] as $keyObserv => $valueObserv) {
                            if(!empty(trim($valueObserv['descripcion']))){
                                $stringObserv = $stringObserv . $valueObserv['descripcion'] . ', ';
                            }
                        }
                        if(empty(trim($stringObserv))){
                            $stringObserv = null;
                        }else{
                            $stringObserv = substr(($stringObserv),0,-2);
                        }
                    }else{
                        if(!empty(trim($item['observacion']))){
                            $stringObserv = $item['observacion'];
                        }else{
                            $stringObserv = null;
                        }
                    }
                    if(!empty(trim($stringObserv))){
                        $observacion_lines = str_split('- '.$stringObserv, 19);
                        foreach ($observacion_lines as $k => $l) {
                            $l = trim($l);
                            $observacion_lines[$k] = self::addSpacesString($l, 19);
                        }
                    
                        $counter = 0;
                        $temp = [];
                        $temp[] = count($observacion_lines);
                        $counter = max($temp);
                    
                        for ($i = 0; $i < $counter; $i++) {
                            $line = '';
                            if (isset($observacion_lines[$i])) {
                                $line .= ($observacion_lines[$i]);
                            }
                    
                            $printer->text(self::addSpacesString('',4).$line . "\n");
                        }
                    }
                }
                $counter = 0;
            }
            $printer->selectPrintMode();
            
            $impuesto      = round(floatval($cuenta['impuesto']),2);
            $base          = number_format($total/(1+($impuesto/100)),2);
            $impuesto_porc = number_format(($total-($total/(1+($impuesto/100)))),2);
            $total         = number_format($total,2);

            $printer->text(self::addSpacesNumber('Base:', 38) . self::addSpacesNumber($base, 10) . "\n");
            $printer->text(self::addSpacesNumber('Impuesto '.$impuesto.'%:', 38) . self::addSpacesNumber($impuesto_porc, 10) . "\n");

            $descuento = $cuenta['descuento'];
            if($descuento){
                $printer->setTextSize(1,1);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);

                $desc_tipo = $descuento['desc_tipo'];
                $desc_cant = $descuento['desc_cant'];
                if($desc_tipo == 1){
                    $descuento_cantidad = number_format($total * ($desc_cant/100),2);
                    $printer->text(self::addSpacesNumber('Descuento '.$desc_cant.'%:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }else if($desc_tipo == 2){
                    $descuento_cantidad = number_format($desc_cant,2);
                    $printer->text(self::addSpacesNumber('Descuento:', 38) . self::addSpacesNumber($descuento_cantidad, 10) . "\n");
                }
                $efec_tarj = number_format(floatval($descuento['efectivo'])+floatval($descuento['tarjeta']),2);
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total a pagar:', 38) . self::addSpacesNumber($efec_tarj, 10) . "\n");
                $printer->setTextSize(1,1);
            }else{
                $printer->setTextSize(2,2);
                $printer->text(self::addSpacesNumber('Total:', 38) . self::addSpacesNumber($total, 10) . "\n");
                $printer->setTextSize(1,1);
            }

            $pago_tipo = $cuenta['pago_tipo'];
            if($pago_tipo){
                $printer->text("\n");
                $printer->setJustification(Printer::JUSTIFY_LEFT);
                $printer->selectPrintMode();
                $printer->setEmphasis(false);
                //$printer->text('Tipo de Pago: '."\n");
                $efectivo = floatval($pago_tipo['efectivo']);
                $tarjeta = floatval($pago_tipo['tarjeta']);
                $tipo_pago_text = 'Tipo de Pago';
                $efectivo_text = '';
                $tarjeta_text = '';
                if($efectivo>0){
                    $efectivo_text = 'Efectivo: '.$efectivo;
                    //$printer->text('Efectivo: '.self::sanear_string($efectivo)."\n");
                }
                if($tarjeta>0){
                    $tarjeta_text = 'Tarjeta: '.$tarjeta;
                    //$printer->text('Tarjeta: '.self::sanear_string($tarjeta)."\n");
                }
                $propina = floatval($pago_tipo['propina']);
                $propina_efec = floatval($pago_tipo['propina_efec']);
                $propina_tarj = floatval($pago_tipo['propina_tarj']);
                $propina_text = '';
                $propina_efec_text = '';
                $propina_tarj_text = '';
                if($propina == 1){
                    $propina_text = 'Propina';
                    //$printer->text("\n");
                    //$printer->text('Propina: '."\n");
                    if($propina_efec>0){
                        $propina_efec_text = 'Efectivo: '.$propina_efec;
                        //$printer->text('Efectivo: '.self::sanear_string($propina_efec)."\n");
                    }
                    if($propina_tarj>0){
                        $propina_tarj_text = 'Tarjeta: '.$propina_tarj;
                        //$printer->text('Tarjeta: '.self::sanear_string($propina_tarj)."\n");
                    }
                }
                $printer->setEmphasis(true);
                $printer->text(self::addSpacesString($tipo_pago_text, 24) . self::addSpacesString($propina_text, 24) . "\n");
                $printer->setEmphasis(false);
                $printer->text(self::addSpacesString($efectivo_text, 24) . self::addSpacesString($propina_efec_text, 24) . "\n");
                $printer->text(self::addSpacesString($tarjeta_text, 24) . self::addSpacesString($propina_tarj_text, 24) . "\n");
                $tipo_pago = floatval($pago_tipo['pago_tipo']);
                $pago_con = floatval($pago_tipo['pago_con']);
                $vuelto = $pago_con-floatval($efectivo+$propina_efec);
                $tarjeta_id_text = $pago_tipo['tarjeta_id_text'];
                if($tipo_pago == 1){
                    $printer->text("\n");
                    $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                    $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                }else if($tipo_pago == 2){
                    $printer->text("\n");
                    $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                }else if($tipo_pago == 3){
                    $printer->text("\n");
                    $printer->text('Pago con: '.self::sanear_string($pago_con)."\n");
                    $printer->text('Vuelto: '.self::sanear_string($vuelto)."\n");
                    $printer->text("\n");
                    $printer->text('Tipo de Tarjeta: '.self::sanear_string($tarjeta_id_text)."\n");
                }
            }

            $printer->text("\n");
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            if($json_fact_elect['cliente_numero_de_documento']){
                $printer->text(self::sanear_string($json_fact_elect['cliente_denominacion'])."\n");
                $printer->text("\n");
            }
            //self::sanear_string($cuenta['p_c_nombre'])
            $printer->text('Cajero: ' . self::sanear_string($cuenta['p_c_nombre']) . "\n");

            $printer->text("\n");
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text('¡ Gracias por su compra !' . "\n");
            $printer->text("\n");
            $printer->text('Consulte su documento en el portal web:' . "\n");
            $printer->text('nubefact.com/20601363029' . "\n");
            $printer->setJustification();
            $printer->text("\n");
            if(($json_fact_elect['tipo'] == 3)||($json_fact_elect['tipo'] == 4)){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json(true);

        }catch(Exception $e){
            return Response::json($e);
        }
    }

    //ticket Vale
    public function ticketVale(Request $req) {
        try{
            $connector = new NetworkPrintConnector($req['impresora_principal']);
            $printer = new Printer($connector);
            //$printer->setFont(Printer::FONT_A);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text($req['e_razon_social']."\n");
            $printer->text($req['l_direccion']."\n");
            $printer->text('Telf: '.$req['l_telefono'].' / RUC: '. $req['e_codigo'] ."\n");
            $printer->text('Fecha: '.$req['fecha']."\n");

            $printer->feed();
            $printer->setPrintLeftMargin(0);
            $printer->setEmphasis(false);
            $printer->selectPrintMode();
            $printer->setJustification();
            if($req['vcom_nombre']){
                $printer->text('Comprobante: '.self::sanear_string($req['vcom_nombre'])."\n");
            }
            if($req['vcc_nombre']){
                $printer->text('Categoria: '.self::sanear_string($req['vcc_nombre'])."\n");
            }
            $printer->text('Concepto: '.self::sanear_string($req['vc_nombre'])."\n");
            if($req['tipo'] == 1){
                $printer->text('Tipo: Ingreso'."\n");
            }else if($req['tipo'] == 2){
                $printer->text('Tipo: Egreso'."\n");
            }
            $printer->text('Observacion: '.self::sanear_string($req['observacion'])."\n");
            $printer->text('Monto: '.$req['monto']."\n");

            if($req['estado'] == 1){
                $printer->text("\n");
                $printer->text("DNI: ___________________________________________"."\n");
                $printer->text("Nombres: _______________________________________"."\n");
                $printer->text("\n");
                $printer->text("Firma: _________________________________________"."\n");
            }

            $printer->text("\n");
            $printer->text('Cajero: ' . self::sanear_string($req['p_c_nombre']) . "\n");

            if($req['estado'] == 0){
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->selectPrintMode();
                $printer->setEmphasis(true);
                $printer->text("ANULADO"."\n");
                $printer->setEmphasis(false);
                $printer->text("\n");
            }
            
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return Response::json($req);

        }catch(Exception $e){
            return Response::json($e);
        }
    }

    //parametrizado
    public function imprimir(Request $req) {
        try{
            //$req->json()->all();

            $profile = CapabilityProfile::load("TM-T88IV");
            $connector = new NetworkPrintConnector($req['ip']);
            $printer = new Printer($connector, $profile);

            $lista = $req['lineas'];
            foreach ($lista as $key => $value) {
                $printer->selectPrintMode(intval($value['selectPrintMode']));
                $printer->setColor(intval($value['setColor']));
                $printer->setDoubleStrike(($value['setDoubleStrike']==='true')?true:false);
                $printer->setEmphasis(($value['setEmphasis']==='true')?true:false);
                $printer->setFont(intval($value['setFont']));
                $printer->setJustification(intval($value['setJustification']));
                $printer->setLineSpacing(intval($value['setLineSpacing']));
                $printer->setPrintLeftMargin(intval($value['setPrintLeftMargin']));
                //$printer->setPrintWidth(intval($value['setPrintWidth']));
                $printer->setReverseColors(($value['setReverseColors']==='true')?true:false);
                $printer->setTextSize(intval($value['setTextSize'][0]),intval($value['setTextSize'][1]));
                $printer->setUnderline(intval($value['setUnderline']));
                $br = intval($value['br']);
                if($br>0){
                    /*
                    if(strlen($value['text']) >= 48){
                        $printer->text($value['text']);
                    }else{
                        $printer->text(' '.$value['text']);
                    }
                    */
                    $text = $value['text'];
                    if(isset($value['spacesLeft'])){
                        $spacesLeft = intval($value['spacesLeft']);
                        for($i=0;$i<$spacesLeft;$i++){
                            $text = ' '.$text;
                        }
                    }
                    if(isset($value['spacesRight'])){
                        $spacesRight = intval($value['spacesRight']);
                        for($i=0;$i<$spacesRight;$i++){
                            $text = $text.' ';
                        }
                    }
                    if(!empty(trim($text))){
                        $printer->text($text);
                    }
                    for($i=0;$i<$br;$i++){
                        $printer->setUnderline(0);
                        $printer->text(' '."\n");
                    }
                }else{
                    $text = $value['text'];
                    if(isset($value['spacesLeft'])){
                        $spacesLeft = intval($value['spacesLeft']);
                        for($i=0;$i<$spacesLeft;$i++){
                            $text = ' '.$text;
                        }
                    }
                    if(isset($value['spacesRight'])){
                        $spacesRight = intval($value['spacesRight']);
                        for($i=0;$i<$spacesRight;$i++){
                            $text = $text.' ';
                        }
                    }
                    if(!empty(trim($text))){
                        $printer->text($text);
                    }
                }
            }

            $printer->feed();
            $printer->cut();
            //$printer->pulse();
            $printer->close();
            return Response::json($req);
        }catch(Exception $e){
            return Response::json($e);
        }
    }

    //parametrizado2
    public function imprimir2(Request $req) {
        try{
            $profile = CapabilityProfile::load("TM-T88IV");
            $connector = new NetworkPrintConnector($req['impresora']['ip']);
            $printer = new Printer($connector, $profile);

            $lista = $req['lineas'];
            foreach ($lista as $key => $value) {
                $printer->selectPrintMode(intval($value['selectPrintMode']));
                $printer->setColor(intval($value['setColor']));
                $printer->setDoubleStrike(($value['setDoubleStrike']==='true')?true:false);
                $printer->setEmphasis(($value['setEmphasis']==='true')?true:false);
                $printer->setFont(intval($value['setFont']));
                $printer->setJustification(intval($value['setJustification']));
                //$printer->setLineSpacing(intval($value['setLineSpacing']));
                $printer->setPrintLeftMargin(intval($value['setPrintLeftMargin']));
                //$printer->setPrintWidth(intval($value['setPrintWidth']));
                $printer->setReverseColors(($value['setReverseColors']==='true')?true:false);
                $printer->setTextSize(intval($value['setTextSize'][0]),intval($value['setTextSize'][1]));
                $printer->setUnderline(intval($value['setUnderline']));
                $br = intval($value['br']);
                if(!isset($value['qr'])){
                    if($br>0){
                        $text = $value['text'];
                        if(isset($value['spacesLeft'])){
                            $spacesLeft = intval($value['spacesLeft']);
                            for($i=0;$i<$spacesLeft;$i++){
                                $text = ' '.$text;
                            }
                        }
                        if(isset($value['spacesRight'])){
                            $spacesRight = intval($value['spacesRight']);
                            for($i=0;$i<$spacesRight;$i++){
                                $text = $text.' ';
                            }
                        }
                        if(!empty(trim($text))){
                            $printer->text($text);
                        }
                        for($i=0;$i<$br;$i++){
                            $printer->setUnderline(0);
                            $printer->text(' '."\n");
                        }
                    }else{
                        $text = $value['text'];
                        if(isset($value['spacesLeft'])){
                            $spacesLeft = intval($value['spacesLeft']);
                            for($i=0;$i<$spacesLeft;$i++){
                                $text = ' '.$text;
                            }
                        }
                        if(isset($value['spacesRight'])){
                            $spacesRight = intval($value['spacesRight']);
                            for($i=0;$i<$spacesRight;$i++){
                                $text = $text.' ';
                            }
                        }
                        if(!empty(trim($text))){
                            $printer->text($text);
                        }
                    }
                }else{
                    $printer->qrCode($value['qr'],Printer::QR_ECLEVEL_L,6,Printer::QR_MODEL_1);
                }
            }
            $printer->feed();
            $printer->cut();
            //$printer->pulse();
            $printer->close();
            return Response::json($req);
        }catch(Exception $e){
            return Response::json($e);
        }
    }

    //parametrizado2
    public function test(Request $req) {
        $validator = Validator::make($req->all(), [
            'ip_address' => ['required','min:7','max:15','ip'],
            //'text' => ['required','max:255'],
        ]);
    
        if ($validator->fails()) {
            return redirect('/')
                ->withInput()
                ->withErrors($validator);
        }

        try {
            //$connector = new NetworkPrintConnector($req['ip_address']);
            $connector = new NetworkPrintConnector($req['ip_address']);
            $printer = new Printer($connector);

            $printer->text($req['text']);

            $printer->feed();
            $printer->cut();
            $printer->pulse();
            $printer->close();

            return redirect('/');
        }catch(Exception $e){
            return redirect('/')->withErrors(['connection' => 'Hubo un error de conección, esto usualmente se debe a que indicó una IP incorrecta o que la conexión fisica de su impresora esta fallando.'])->withInput();
        }
    }

    /**
        selectPrintMode($mode){
            Select print mode(s).

            Parameters:

            int $mode: The mode to use. Default is Printer::MODE_FONT_A, with no special formatting. This has a similar effect to running initialize().
            Several MODE_* constants can be OR'd together passed to this function's $mode argument. The valid modes are:

            MODE_FONT_A
            MODE_FONT_B
            MODE_EMPHASIZED
            MODE_DOUBLE_HEIGHT
            MODE_DOUBLE_WIDTH
            MODE_UNDERLINE
        }

        setBarcodeHeight($height){
            Set barcode height.
            Parameters:
            int $height: Height in dots. If not specified, 8 will be used.
        }

        setBarcodeWidth($width){
            Set barcode bar width.
            Parameters:
            int $width: Bar width in dots. If not specified, 3 will be used. Values above 6 appear to have no effect.
        }

        setColor($color){
            Select print color - on printers that support multiple colors.
            Parameters:
            int $color: Color to use. Must be either Printer::COLOR_1 (default), or Printer::COLOR_2
        }

        setDoubleStrike($on){
            Turn double-strike mode on/off.
            Parameters:
            boolean $on: true for double strike, false for no double strike.
        }

        setEmphasis($on){
            Turn emphasized mode on/off.
            Parameters:
            boolean $on: true for emphasis, false for no emphasis.
        }

        setFont($font){
            Select font. Most printers have two fonts (Fonts A and B), and some have a third (Font C).
            Parameters:
            int $font: The font to use. Must be either Printer::FONT_A, Printer::FONT_B, or Printer::FONT_C.
        }

        setJustification($justification){
            Select justification.
            Parameters:
            int $justification: One of Printer::JUSTIFY_LEFT, Printer::JUSTIFY_CENTER, or Printer::JUSTIFY_RIGHT.
        }

        setLineSpacing($height){
            Set the height of the line.
            Some printers will allow you to overlap lines with a smaller line feed.
            Parameters:
            int $height: The height of each line, in dots. If not set, the printer will reset to its default line spacing.
        }

        setPrintLeftMargin($margin){
            Set print area left margin. Reset to default with Printer::initialize().
            Parameters:
            int $margin: The left margin to set on to the print area, in dots.
        }

        setPrintWidth($width){
            Set print area width. This can be used to add a right margin to the print area. Reset to default with Printer::initialize().
            Parameters:
            int $width: The width of the page print area, in dots.
        }

        setReverseColors($on){
            Set black/white reverse mode on or off. In this mode, text is printed white on a black background.
            Parameters:
            boolean $on: True to enable, false to disable.
        }

        setTextSize($widthMultiplier, $heightMultiplier){
            Set the size of text, as a multiple of the normal size.
            Parameters:
            int $widthMultiplier: Multiple of the regular height to use (range 1 - 8).
            int $heightMultiplier: Multiple of the regular height to use (range 1 - 8).
        }

        setUnderline($underline){
            Set underline for printed text.
            Parameters:
            int $underline: Either true/false, or one of Printer::UNDERLINE_NONE, Printer::UNDERLINE_SINGLE or Printer::UNDERLINE_DOUBLE. Defaults to Printer::UNDERLINE_SINGLE.
        }

        text($str){
            Add text to the buffer. Text should either be followed by a line-break, or feed() should be called after this.
            Parameters:
            string $str: The string to print.
        }

     */
}
