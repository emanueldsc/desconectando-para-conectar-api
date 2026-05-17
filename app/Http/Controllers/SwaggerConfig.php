<?php
namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
	title: 'API Desconectando para Conectar',
	version: '1.0.0',
	description: 'Documentacao oficial da API do projeto Desconectando para Conectar.'
)]
#[OA\Server(
	url: 'http://localhost:8000/api',
	description: 'Servidor Principal'
)]
class SwaggerConfig
{
	#[OA\Get(
		path: '/_docs/health',
		summary: 'Health check da documentacao',
		tags: ['Documentation'],
		responses: [
			new OA\Response(response: 200, description: 'OK')
		]
	)]
	public function docsHealth(): void {}
}
