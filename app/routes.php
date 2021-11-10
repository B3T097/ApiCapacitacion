<?php
declare(strict_types=1);

include_once('mysql.php');

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->get('/hello', function (Request $request, Response $response) {
        $sql = "SELECT * FROM `respuestas`";
        $DB = new MySql();
        $data = $DB->Buscar($sql);
        $info = json_encode(
            array(
                'success' => true,
                'code' => 200,
                'data' => $data
            )
        );
        $response->getBody()->write( $info );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });

    $app->get('/login/{ user }/{ pwd }', function (Request $request, Response $response, array $args) {
        $DB = new MySql();
        $user = $args['user'];
        $pwd = $args['pwd'];
        $sql = "SELECT * FROM `usuarios` WHERE `correo` = ? AND `password` = ?;";
        $res = $DB->Buscar_Seguro( $sql, array( $user, $pwd ) );
        if ( count( $res ) == 0 ) {
            $info = json_encode(
                array(
                    'success' => false,
                    'code' => 400,
                    'data' => $res
                )
            );
        } else {
            $info = json_encode(
                array(
                    'success' => true,
                    'code' => 200,
                    'data' => $res
                )
            );
        }
        $response->getBody()->write( $info );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });

    $app->get('/lecciones/{ user }', function (Request $request, Response $response, array $args) {
        $DB = new MySql();
        $user = $args['user'];
        $sql = "SELECT e.nombre, e.descripcion, ue.status, e.id
                FROM encuestas AS e
                INNER JOIN usuarios_encuestas AS ue ON ue.id_encuesta = e.id
                WHERE ue.id_usuario = ?;";
        $res = $DB->Buscar_Seguro( $sql, array( $user ) );
        if ( count( $res ) == 0 ) {
            $info = json_encode(
                array(
                    'success' => false,
                    'code' => 400,
                    'data' => $user
                )
            );
        } else {
            $info = json_encode(
                array(
                    'success' => true,
                    'code' => 200,
                    'data' => $res
                )
            );
        }
        $response->getBody()->write( $info );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });

    $app->get('/leccion/{ encuesta }', function (Request $request, Response $response, array $args) {
        $DB = new MySql();
        $encuesta = intval( $args['encuesta'] );
        if ($encuesta == 0) {
            $info = json_encode(
                array(
                    'success' => false,
                    'code' => 400,
                    'data' => $encuesta
                )
            );
        } 
        else {
            $sql = "SELECT l.*
                    FROM lecciones AS l
                    INNER JOIN usuarios_encuestas AS ue ON ue.id_encuesta = l.id_encuesta
                    WHERE l.id_encuesta = ?;";
            $res = $DB->Buscar_Seguro( $sql, array( $encuesta ) );
            if ( count( $res ) == 0 ) {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $user
                    )
                );
            } else {
                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $res
                    )
                );
            }
        }
        $response->getBody()->write( $info );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });

    $app->get('/preguntas/{ encuesta }', function (Request $request, Response $response, array $args) {
        $DB = new MySql();
        $encuesta = ( $args['encuesta'] );
        if ($encuesta == 0) {
            $info = json_encode(
                array(
                    'success' => false,
                    'code' => 400,
                    'data' => $encuesta
                )
            );
        } else {
            $sql = "SELECT p.pregunta, r.id AS idRespuesta, r.id_pregunta, r.respuesta 
                    FROM preguntas AS p 
                    INNER JOIN respuestas AS r ON r.id_pregunta = p.id 
                    WHERE p.id_encuesta = ?
                    ORDER BY r.id_pregunta;";
            $res = $DB->Buscar_Seguro( $sql, array( $encuesta ) );
            if ( count( $res ) == 0 ) {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $user
                    )
                );
            } else {
                $preguntas = [];
                $aux=[];
                $inicio = 0;
                $final = 0;
                $auxInicio = false;
                foreach ($res as $key => $value) {
                    if ( !$auxInicio ) {
                        $inicio = $value['id_pregunta'];
                        $auxInicio = true;
                    }
                    $final = $value['id_pregunta'];
                    $preguntas[$value['id_pregunta']][] = $value;
                }

                $letras = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j'];

                for ($i=$inicio; $i < $final + 1; $i++) {
                    $auxRespuestas = array();
                    for ($e=0; $e < count( $preguntas[$i] ); $e++) {
                        $auxRespuestas[]  = array(
                            'resp'        => $preguntas[$i][$e]['respuesta'],
                            'idRespuesta' => $preguntas[$i][$e]['idRespuesta']
                        );
                    }

                    array_push($aux, array( 
                        'idPregunta' => $preguntas[$i][0]['id_pregunta'],
                        'pregunta' => $preguntas[$i][0]['pregunta'],
                        'respuestas' => $auxRespuestas
                    ));
                }


                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $aux
                    )
                );
            }
        }
        $response->getBody()->write( $info );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });

    $app->get('/respuestas/{ id }', function (Request $request, Response $response, array $args) {
        $DB = new MySql();
        $idPregunta = $args['id'];
        $sql = "SELECT `id_encuesta` FROM `preguntas` WHERE id = ?;";
        $res = $DB->Buscar_Seguro( $sql, array( $idPregunta ) );
        if ( count( $res ) != 0 ) {
            $sql2 = "SELECT * FROM `respuestas_correctas` WHERE id_encuesta = ?";
            $res2 = $DB->Buscar_Seguro( $sql2, array( $res[0]['id_encuesta'] ) );
            if ( count( $res2 ) == 0 ) {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $user
                    )
                );
            } else {
                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $res2
                    )
                );
            }
        } else {
            $info = json_encode(
                array(
                    'success' => false,
                    'code' => 400,
                    'data' => $user
                )
            );
        }
        $response->getBody()->write( $info );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });

    $app->get('/statusEncuesta/{ encuesta }/{ user }/{ status }', function (Request $request, Response $response, array $args) {
        $DB = new MySql();
        $encuesta = $args['encuesta'];
        $user = $args['user'];
        $status = $args['status'];
        $sql = "UPDATE `usuarios_encuestas` SET `status`= ? WHERE `id_usuario` = ? AND `id_encuesta` = ?;";
        $res = $DB->Buscar_Seguro( $sql, array( $status, $user, $encuesta ) );
        if ( $res == 200 || $res == '200' ) {
            $info = json_encode(
                array(
                    'success' => true,
                    'code' => 200,
                    'data' => $res
                )
            );
        } else {
            $info = json_encode(
                array(
                    'success' => false,
                    'code' => 400,
                    'data' => []
                )
            );
        }
        $response->getBody()->write( $info );
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Access-Control-Allow-Origin', '*');
    });

    $app->group('/CMS', function (Group $group) {
        $group->get('/login/{ user }/{ pwd }', function (Request $request, Response $response, array $args) {
            $DB = new MySql();
            $user = $args['user'];
            $pwd = $args['pwd'];
            $sql = "SELECT * FROM `usuarios` WHERE `correo` = ? AND `password` = ? AND rol = 1;";
            $res = $DB->Buscar_Seguro( $sql, array( $user, $pwd ) );
            if ( count( $res ) == 0 ) {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $res
                    )
                );
            } else {
                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $res
                    )
                );
            }
            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $group->get('/users', function (Request $request, Response $response, array $args) {
            $DB = new MySql();
            $sql = "SELECT u.id, u.nombre, 
            ( SELECT COUNT(id) FROM usuarios_encuestas WHERE `status` = 1 AND `id_usuario` = u.id ) AS contestadas, 
            ( SELECT COUNT(id) FROM usuarios_encuestas WHERE `status` = 0 AND `id_usuario` = u.id ) AS faltantes 
            FROM usuarios AS u WHERE rol = 2;";
            $res = $DB->Buscar( $sql );
            if ( count( $res ) == 0 ) {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $res
                    )
                );
            } else {
                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $res
                    )
                );
            }
            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $group->get('/user/{ id }', function (Request $request, Response $response, array $args) {
            $id = $args['id'];
            $DB = new MySql();
            $sql = "SELECT * FROM usuarios WHERE id = ?";
            $res = $DB->Buscar_Seguro( $sql, array( $id ) );
            if ( count( $res ) == 0 ) {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $res
                    )
                );
            } else {
                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $res
                    )
                );
            }
            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $group->post('/EditUser', function (Request $request, Response $response, array $args) {

            $data = $request->getParsedBody();

            $DB = new MySql();
            if ($data['id'] == 0 || $data['id'] == '0') {
                $sql = "INSERT INTO `usuarios`(`nombre`, `correo`, `password`, `area`, `puesto`, `rol`) 
                VALUES (?, ?, ?, ?, ?, ?);";
                $params = array( $data['nombre'], $data['correo'], $data['pwd'], $data['area'], $data['puesto'], $data['rol'] );
            } else {
                $sql = "UPDATE `usuarios` SET `nombre`=?,`correo`=?,`password`=?,`area`=?,`puesto`=?,`rol`=? WHERE `id`=?;";
                $params = array( $data['nombre'], $data['correo'], $data['pwd'], $data['area'], $data['puesto'], $data['rol'], $data['id'] );
            }

            $res = $DB->Ejecutar_Seguro( $sql, $params );
            if ( $res == 200 || $res == '200' ) {
                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $res,
                        'message' => 'Los datos se guardaron exitosamente'
                    )
                );
            } else {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $res,
                        'message' => 'Ocurrio un error al guardar la información.'
                    )
                );
            }

            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });
    });
};
