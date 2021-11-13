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

        $group->get('/encuestas', function (Request $request, Response $response, array $args) {
            $DB = new MySql();
            $sql = "SELECT * FROM encuestas";
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

        $group->get('/encuesta/{ id }', function (Request $request, Response $response, array $args) {
            $id = $args['id'];
            $DB = new MySql();
            $sql = "SELECT e.*, l.leccion, l.video FROM encuestas AS e INNER JOIN lecciones AS l ON l.id_encuesta = e.id WHERE e.id = ?;";
            $res = $DB->Buscar_Seguro( $sql, array( $id ) );
            $sqlUsers = "SELECT ue.*,  u.nombre
            FROM usuarios_encuestas AS ue 
            INNER JOIN usuarios AS u ON u.id = ue.id_usuario
            WHERE id_encuesta = ?;";
            $resUsers = $DB->Buscar_Seguro( $sqlUsers, array( $id ) );
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
                        'data' => array(
                            'encuesta' => $res,
                            'usuarios' => $resUsers
                        )
                    )
                );
            }
            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $group->post('/EditEncuesta', function (Request $request, Response $response, array $args) {

            $data = $request->getParsedBody();

            $DB = new MySql();
            if ($data['id'] == 0 || $data['id'] == '0') {
                $sqlEncuesta = "INSERT INTO `encuestas`(`nombre`, `descripcion`) VALUES ( ?, ? )";
                $params = array( $data['nombre'], $data['descripcion'] );
                $res = $DB->Ejecutar_Seguro( $sqlEncuesta, $params );
                $idEncuesta = $DB->Buscar('SELECT MAX(id) AS id FROM encuestas LIMIT 1;')[0]['id'];
                $Leccion = $DB->Ejecutar_Seguro("INSERT INTO `lecciones`(`id_encuesta`, `leccion`, `video`) VALUES ( ?, ?, ? )", array( $idEncuesta, $data['leccion'], $data['video'] ));
                $arrayUsuarios = explode( ',', $data['usuarios'] );
                foreach ($arrayUsuarios as $usuario) {
                    $addUsuarioEncuesta = $DB->Ejecutar_Seguro("INSERT INTO `usuarios_encuestas`( `id_usuario`, `id_encuesta`) VALUES ( ?, ? )", array( $usuario, $idEncuesta ));
                }
            } else {
                $Encuesta = $DB->Ejecutar_Seguro( "UPDATE `encuestas` SET `nombre`= ?,`descripcion`= ? WHERE `id`= ?", array( $data['nombre'], $data['descripcion'], $data['id'] ) );
                $Leccion = $DB->Ejecutar_Seguro( "UPDATE `lecciones` SET `leccion`= ?,`video`= ? WHERE `id_encuesta`= ?", array( $data['leccion'], $data['video'], $data['id'] ) );
                $deleteUsuarios = $DB->Ejecutar("DELETE FROM `usuarios_encuestas` WHERE id_encuesta = " . $data['id']);
                $arrayUsuarios = explode( ',', $data['usuarios'] );
                foreach ($arrayUsuarios as $usuario) {
                    $addUsuarioEncuesta = $DB->Ejecutar_Seguro("INSERT INTO `usuarios_encuestas`( `id_usuario`, `id_encuesta`) VALUES ( ?, ? )", array( $usuario, $data['id'] ));
                }
            }

            if ( $Leccion == 200 || $Leccion == '200' ) {
                $info = json_encode(
                    array(
                        'success' => true,
                        'code' => 200,
                        'data' => $Leccion,
                        'message' => 'Los datos se guardaron exitosamente'
                    )
                );
            } else {
                $info = json_encode(
                    array(
                        'success' => false,
                        'code' => 400,
                        'data' => $Leccion,
                        'message' => 'Ocurrio un error al guardar la información.'
                    )
                );
            }

            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $group->post('/updatePreguntas', function (Request $request, Response $response, array $args) {

            $data = $request->getParsedBody();
            $preguntas = json_decode( $data['preguntas'], true );
            $encuesta = $data['encuesta'];
            $DB = new MySql();
            $count = [];

            foreach ($preguntas as $key => $value) {
                $idPregunta = intval( $value['idPregunta'] );
                $pregunta = $value['pregunta'];
                if ( $idPregunta != 0 ) {
                    $sql = "UPDATE `preguntas` SET `pregunta`= ? WHERE `id`= ? AND `id_encuesta`= ?";
                    $params = array( $pregunta, $idPregunta, $encuesta );
                    $resPregunta = $DB->Ejecutar_Seguro( $sql, $params );
                    array_push( $count, $resPregunta );
                    foreach ($value['respuestas'] as $key2 => $value2) {
                        $idRespuesta = intval( $value2['idRespuesta'] );
                        $respuesta = $value2['resp'];
                        if ( $idRespuesta != 0 ) {
                            $sql = "UPDATE `respuestas` SET `id_encuesta`= ?,`id_pregunta`= ?,`respuesta`= ? WHERE `id`= ?";
                            $params = array( $encuesta, $idPregunta, $respuesta, $idRespuesta );
                            $resRespuesta = $DB->Ejecutar_Seguro( $sql, $params );
                            array_push( $count, $resRespuesta );
                        } else {
                            $sql = "INSERT INTO `respuestas`(`id_encuesta`, `id_pregunta`, `respuesta`) VALUES (?, ?, ?);";
                            $params = array( $encuesta, $idPregunta, $respuesta );
                            $resRespuesta = $DB->Ejecutar_Seguro( $sql, $params );
                            array_push( $count, $resRespuesta );
                        }
                    }
                } else {
                    $sql = "INSERT INTO `preguntas`(`id_encuesta`, `pregunta`) VALUES ( ?, ? )";
                    $params = array( $encuesta, $pregunta );
                    $resPregunta = $DB->Ejecutar_Seguro( $sql, $params );
                    $idPregunta = $DB->Buscar("SELECT MAX(id) AS id FROM preguntas;")[0]['id'];
                    array_push( $count, $resPregunta );
                    foreach ($value['respuestas'] as $key2 => $value2) {
                        $idRespuesta = intval( $value2['idRespuesta'] );
                        $respuesta = $value2['resp'];
                        if ( $idRespuesta != 0 ) {
                            $sql = "UPDATE `respuestas` SET `id_encuesta`= ?,`id_pregunta`= ?,`respuesta`= ? WHERE `id`= ?";
                            $params = array( $encuesta, $idPregunta, $respuesta, $idRespuesta );
                            $resRespuesta = $DB->Ejecutar_Seguro( $sql, $params );
                            array_push( $count, $resRespuesta );
                        } else {
                            $sql = "INSERT INTO `respuestas`(`id_encuesta`, `id_pregunta`, `respuesta`) VALUES (?, ?, ?);";
                            $params = array( $encuesta, $idPregunta, $respuesta );
                            $resRespuesta = $DB->Ejecutar_Seguro( $sql, $params );
                            array_push( $count, $resRespuesta );
                        }
                    }
                }
            }

            $info = json_encode(
                array(
                    'success' => true,
                    'code' => 200,
                    'data' => $count,
                    'message' => 'Los datos se guardaron exitosamente'
                )
            );

            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });

        $group->get('/respuestas/{ leccion }', function (Request $request, Response $response, array $args) {
            $leccion = $args['leccion'];
            $DB = new MySql();
            $sql = "SELECT * FROM respuestas_correctas WHERE id_encuesta = ?";
            $res = $DB->Buscar_Seguro( $sql, array( $leccion ) );
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

        $group->post('/updateRespuestas', function (Request $request, Response $response, array $args) {

            $data = $request->getParsedBody();
            $respuestas = json_decode( $data['respuestas'], true );
            $encuesta = $data['encuesta'];
            $DB = new MySql();
            $count = [];

            $delete = $DB->Ejecutar_Seguro( "DELETE FROM respuestas_correctas WHERE id_encuesta = ?", array( $encuesta ) );
            foreach ($respuestas as $key => $value) {
                $idPregunta = intval( explode( '-', $value['idPregunta'] )[1] );
                $respuesta = explode( '-', $value['idRespuesta'] )[1];
                $sql = "INSERT INTO `respuestas_correctas`(`id_encuesta`, `id_pregunta`, `id_respuesta`) VALUES (?, ?, ?)";
                $insert = $DB->Ejecutar_Seguro( $sql, array( $encuesta, $idPregunta, $respuesta ) );
            }

            $info = json_encode(
                array(
                    'success' => true,
                    'code' => 200,
                    'data' => $count,
                    'respuestas' => $respuestas,
                    'idEncuesta' => $encuesta,
                    'message' => 'Los datos se guardaron exitosamente'
                )
            );

            $response->getBody()->write( $info );
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', '*');
        });
        
    });
};
