<?php

namespace App\Http\Controllers;

use App\Services\JsonDB;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public $strFecha;
    public $horaLimitePuntual;
    public $horaLimiteDerivado;
    public $limitePuntual;
    public $limiteDerivado;
    public $horaActual;

    public function __construct()
    {
        $this->strFecha = 'Y-m-d';
        // $this->horaActual = time();
        $this->horaActual = strtotime(date("{$this->strFecha} H:i:s"));

        $config_system = DB::table('config_system')->get()->keyBy('config');

        $this->horaLimitePuntual = $config_system['horaLimitePuntual']?->values ?? "08:30:59";
        $this->horaLimiteDerivado = $config_system['horaLimiteDerivado']?->values ?? "10:30:00";

        $this->limitePuntual = strtotime(date("{$this->strFecha} {$this->horaLimitePuntual}"));
        $this->limiteDerivado = strtotime(date("{$this->strFecha} {$this->horaLimiteDerivado}"));
    }

    public function getDay($date)
    {
        $day = strtolower(date('l', strtotime($date)));
        $map = [
            'monday' => 'lunes',
            'tuesday' => 'martes',
            'wednesday' => 'miercoles',
            'thursday' => 'jueves',
            'friday' => 'viernes',
            'saturday' => 'sabado',
            'sunday' => null,
        ];

        return $map[$day] ?? null;
    }

    public function obtenerModulos2($tipo_acceso, $sistema)
    {
        $filteredIds = [
            '0' => ['1' => [], '2' => [], '3' => ["1", "2", "3", "4", "5"], '4' => ['6'], '5' => []],
            '1' => ['5' => []],
            '2' => ['1' => [], '2' => [], '4' => ['6'], '5' => []],
            '3' => ['1' => [], '5' => []],
            '4' => ['1' => [], '2' => [], '4' => ['6'], '5' => []],
            '5' => ['1' => [], '5' => []],
            '6' => ['1' => [], '5' => []],
            '7' => ['1' => [], '2' => [], '4' => ['6'], '5' => []],
        ][$sistema === 1 ? 0 : $tipo_acceso];

        $id_menus = array_keys($filteredIds);

        $id_sub_menus = [];
        foreach ($filteredIds as $value) {
            $id_sub_menus = array_merge($id_sub_menus, $value);
        }

        $sub_menus = JsonDB::table('sub_menu')->select("id", "id_menu", "descripcion", "categoria", "ruta")
            ->whereIn('id', $id_sub_menus)
            ->get()
            ->groupBy('categoria');

        $menus = JsonDB::table('menu')->select("id", "descripcion", "icon", "ruta", "submenu", "sistema", "orden")
            ->whereIn('id', $id_menus)
            ->get();

        return response()->json(['sub_menus' => $sub_menus]);
    }

    public function obtenerModulos($tipo_acceso, $sistema)
    {
        $filteredIds = [
            '0' => ['1' => [], '2' => [], '3' => ["1", "2", "3", "4", "5"], '4' => ['6'], '5' => []],
            '1' => ['5' => []],
            '2' => ['1' => [], '2' => [], '4' => ['6'], '5' => []],
            '3' => ['1' => [], '5' => []],
            '4' => ['1' => [], '2' => [], '4' => ['6'], '5' => []],
            '5' => ['1' => [], '5' => []],
            '6' => ['1' => [], '5' => []],
            '7' => ['1' => [], '2' => [], '4' => ['6'], '5' => []],
        ][$sistema === 1 ? 0 : $tipo_acceso];
        // Decodificar el JSON base64 recibido con los IDs a filtrar

        // Definir los tipos de menú permitidos según el tipo de acceso
        $tipo_menu = [0];
        if ($tipo_acceso == 5) {
            array_push($tipo_menu, 1);
        }

        $inventario_asignado = DB::table('inventario_vehicular_asignado')->where('user_id', Auth::user()->user_id)->exists();

        if (in_array(Auth::user()->rol_system, [2, 4 ,7]) || $inventario_asignado || Auth::user()->sistema == 1) {
            $filteredIds[6] = [];
        }

        // Obtener y filtrar los menús del JSON
        $menu = JsonDB::table('menu')->get()
            ->filter(function ($item) use ($tipo_menu, $filteredIds) {
                return $item->estatus == 1 &&
                    in_array($item->sistema, $tipo_menu) &&
                    array_key_exists($item->id, $filteredIds);
            })
            ->sortBy('orden')
            ->values();

        // Obtener y filtrar los submenús del JSON
        $submenus = JsonDB::table('sub_menu')->get()
            ->filter(function ($item) use ($filteredIds) {
                // Se valida que el menú padre (id_menu) esté dentro de los IDs filtrados
                return $item->estatus == 1 && isset($filteredIds[$item->id_menu]);
            })
            ->filter(function ($item) use ($filteredIds) {
                // Si en el JSON filtrado se especificaron submenús para el menú,
                // se valida que el submenú esté incluido
                $submenuIds = $filteredIds[$item->id_menu];
                if (!empty($submenuIds)) {
                    return in_array($item->id, $submenuIds);
                }
                return true;
            })
            ->groupBy('id_menu');

        // Determinar la ruta principal
        $rutaPrincipal = null;
        if ($menu->isNotEmpty()) {
            $primerMenu = $menu->first();
            // Si el JSON indica que este menú posee submenús y se tienen registros filtrados
            if (!empty($filteredIds[$primerMenu->id]) && $submenus->has($primerMenu->id)) {
                $primerSubmenu = $submenus[$primerMenu->id]->first();
                $rutaPrincipal = $primerSubmenu->ruta;
            } else {
                $rutaPrincipal = $primerMenu->ruta;
            }
        }

        // Combinar menús y submenús en la estructura deseada
        $menus = $menu->map(function ($item) use ($submenus, $filteredIds) {
            $menuId = $item->id;

            // Si el JSON dice que este menú no tiene submenús, se deja la propiedad vacía
            if (empty($filteredIds[$menuId])) {
                $item->submenu = [];
                return $item;
            }

            // Si existen submenús para este menú, agruparlos por categoría
            if ($submenus->has($menuId)) {
                $groupedByCategory = $submenus[$menuId]->groupBy(function ($submenu) {
                    return $submenu->categoria ?: 'sin_categoria';
                });

                // Se reasigna la propiedad "submenu" con los submenús agrupados y reindexados
                $item->submenu = $groupedByCategory->map(function ($submenusList) {
                    return $submenusList->values();
                });
            } else {
                $item->submenu = [];
            }
            return $item;
        });

        // Retornar la estructura con los menús y la ruta principal
        return (object) [
            "menus" => $menus,
            "ruta" => $rutaPrincipal
        ];
    }

    public function formatEstado($estado, $field = "")
    {
        $respuesta = "";
        $config = [
            ['color' => 'danger', 'text' => 'Inactivo'],
            ['color' => 'success', 'text' => 'Activo']
        ][$estado];

        switch ($field) {
            case 'change':
                $respuesta = '<i class="fas fa-rotate me-2 text-' . $config['color'] . '"></i>Cambiar Estado';
                break;

            default:
                $respuesta = '<label class="badge badge-' . $config['color'] . '" style="font-size: .7rem;">' . $config['text'] . '</label>';
                break;
        }
        return $respuesta;
    }

    public function formatearNombre(...$args)
    {
        if (count($args) == 1) {
            // Si se pasa un solo argumento, separamos nombres y apellidos
            $partes = explode(' ', trim($args[0]));
            $cantidad = count($partes);

            if ($cantidad < 3) {
                $primero = ucfirst(strtolower($partes[0]));
                $segundo = isset($partes[1]) ? strtoupper(substr($partes[1], 0, 1)) . '.' : '';

                return trim("$primero $segundo");
            }

            $apellidos = array_slice($partes, -2); // Últimos dos elementos como apellidos
            $nombres = array_slice($partes, 0, -2); // El resto como nombres
        } else {
            // Si se pasan nombres y apellidos por separado
            $nombres = explode(' ', trim($args[0]));
            $apellidos = explode(' ', trim($args[1]));
        }

        $primerNombre = ucfirst(strtolower($nombres[0])); // Primer nombre con mayúscula inicial
        $primerApellido = ucfirst(strtolower($apellidos[0])); // Primer apellido con mayúscula inicial
        $inicialSegundoApellido = isset($apellidos[1]) ? strtoupper(substr($apellidos[1], 0, 1)) . '.' : ''; // Inicial del segundo apellido

        return trim("$primerNombre $primerApellido $inicialSegundoApellido");
    }

    public function DropdownAcciones($arr_acciones, $notificacion = false)
    {
        // Validaciones
        if (!is_array($arr_acciones)) {
            throw new Exception("El parámetro enviado tiene que ser un array");
        }

        if (empty($arr_acciones)) {
            throw new Exception("El array no puede estar vacío");
        }

        if (!array_key_exists('button', $arr_acciones)) {
            throw new Exception("La clave 'button' no existe en el array.");
        }
        $sinAcciones = empty($arr_acciones['button']);

        // Título del dropdown
        $str_title = '<h6 class="dropdown-header text-secondary d-flex justify-content-between align-items-center">:titulo <i class="fas fa-gear"></i></h6>';
        $tittle = str_replace(":titulo", $arr_acciones['tittle'] ?? "Acciones", $str_title);

        // Botones del dropdown
        $str_button = '<button class="dropdown-item py-2 :clase" :funcion :attr>:texto</button>';
        $button = '';

        foreach ($arr_acciones['button'] as $val) {
            if ($val) {
                $arr_btn = [
                    ':clase' => $val['clase'] ?? '',
                    ':funcion' => array_key_exists('funcion', $val) ? ('onclick="' . ($val['funcion'] ?? "alert('prueba de alerta')") . '"') : '',
                    ':texto' => $val['texto'] ?? 'Prueba',
                    ':attr' => $val['attr'] ?? '',
                ];
                $button .= str_replace(array_keys($arr_btn), array_values($arr_btn), $str_button);
            }
        }

        // Estructura del dropdown
        $dropDown = '
            <div class="btn-group dropdown shadow-0">
                ' . ($notificacion ? '
                       <span class="position-absolute badge bg-danger" style="top: -4px;left: -10px;z-index:9;">
                            <i class="fas fa-bell" style="font-size: larger;min-width: .5em;"></i>
                            <span class="visually-hidden">Nuevas alertas</span>
                        </span>' : '') . '
                <button
                    type="button"
                    class="' . ($sinAcciones ? 'disabled' : 'dropdown-toggle') . ' btn btn-tertiary hover-btn btn-sm p-1 shadow-0"
                    data-mdb-ripple-init
                    aria-expanded="false"
                    data-mdb-dropdown-init
                    data-mdb-ripple-color="dark"
                    data-mdb-parent=".dataTables_scrollBody"
                    data-mdb-dropdown-animation="off"
                    data-mdb-dropdown-initialized="true">
                    <i class="fas fa-' . ($sinAcciones ? 'ban' : 'bars') . '" style="font-size: 1.125em;"></i>
                </button>
                <div class="dropdown-menu">
                    ' . $tittle . $button . '
                </div>
            </div>';

        return $dropDown;
    }

    public function message($title = "", $message = "", $data = null, $status = 200, $e = null): object
    {
        $response = ["success" => $status == 200 ? true : false, "title" => $title, "message" => $message, "time" => now()->format('Y-m-d H:i:s'), "status" => $status];
        $statuses = [
            "success" => ["title" => "Éxito", "range" => range(200, 201)],
            "info" => ["title" => "Atención", "range" => range(202, 399)],
            "warning" => ["title" => "Proceso Fallido", "range" => range(400, 599)],
            "error" => ["title" => "Error interno del Servidor", "range" => range(500, 599)],
        ];
        if (!empty($data)) {
            foreach ($data as $clave => $value) {
                $response[$clave] = $value;
                if (empty($message) && $clave == 'error') {
                    $response["success"] = false;
                    $response["message"] = "Hubo un problema en el servidor. Estamos trabajando para solucionarlo lo antes posible. Por favor, intenta de nuevo más tarde.";
                }
            }
        }
        foreach ($statuses as $icon => $info) {
            if (in_array($status, $info["range"])) {
                $response["icon"] = $icon;
                if (empty($title)) {
                    $response["title"] = $info["title"];
                }
                break;
            }
        }
        if (empty($message)) {
            switch ($status) {
                case 500:
                    $response["message"] = "Ha ocurrido un error inesperado en el servidor.";
                    break;

                case 503:
                    $response["message"] = "El servidor está en mantenimiento o sobrecargado.";
                    break;

                case 422:
                    $response["title"] = "Datos inválidos (errores de validación).";
                    break;
            }
        }
        if ($e !== null) {
            Log::error("[{$e->getCode()}] Error al listar materiales: {$e->getMessage()}\n{$e->getFile()}\n");
        }
        return response()->json($response, (int) $status);
    }

    public function colores($c)
    {
        $coloresPorLetra = [
            'A' => '#E74C3C', // rojo
            'B' => '#8E44AD', // púrpura
            'C' => '#3498DB', // azul
            'D' => '#1ABC9C', // turquesa
            'E' => '#27AE60', // verde
            'F' => '#F1C40F', // amarillo
            'G' => '#E67E22', // naranja
            'H' => '#E84393', // rosado
            'I' => '#2ECC71', // verde claro
            'J' => '#16A085', // verde azulado
            'K' => '#2980B9', // azul intenso
            'L' => '#9B59B6', // violeta
            'M' => '#34495E', // gris azulado
            'N' => '#D35400', // naranja oscuro
            'Ñ' => '#BB8FCE', // violeta suave
            'O' => '#C0392B', // rojo oscuro
            'P' => '#7D3C98', // violeta profundo
            'Q' => '#2471A3', // azul medio
            'R' => '#1F618D', // azul marino
            'S' => '#17A589', // verde marino
            'T' => '#229954', // verde selva
            'U' => '#F39C12', // ámbar
            'V' => '#BA4A00', // marrón
            'W' => '#7E5109', // marrón oscuro
            'X' => '#616A6B', // gris medio
            'Y' => '#2E4053', // gris azulado oscuro
            'Z' => '#3f7175ff'  // gris claro
        ];

        $letra = mb_strtoupper(mb_substr($c, 0, 1, 'UTF-8'), 'UTF-8');
        return $coloresPorLetra[$letra] ?? '#3b71ca';
    }
}
