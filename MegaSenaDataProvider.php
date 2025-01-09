<?php
namespace Xibo\Custom;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Xibo\Widget\Provider\DataProviderInterface;
use Xibo\Widget\Provider\DurationProviderInterface;
use Xibo\Widget\Provider\WidgetProviderInterface;
use Xibo\Widget\Provider\WidgetProviderTrait;

class MegaSenaDataProvider implements WidgetProviderInterface
{
    use WidgetProviderTrait;

    public function fetchData(DataProviderInterface $dataProvider): WidgetProviderInterface
    {
        // Criando uma instância do cliente HTTP (Guzzle)
        $client = new Client();
        
        // URL da API
        $url = 'https://api.guidi.dev.br/loteria/megasena/ultimo';
        
        // Realizando a requisição GET para a API
        $response = $client->get($url);
        
        // Decodificando a resposta JSON da API
        $data = json_decode($response->getBody()->getContents(), true);
        
        // Verificando se a requisição foi bem-sucedida
        if ($data && isset($data['numero'], $data['listaDezenas'])) {
            $numeroConcurso = $data['numero']; // Número do concurso
            
            // Criando as dezenas formatadas com <span>
            $dezenas = array_map(function($dezena) {
                return "<span style='font-size: 140px;
                width: 250px;
                height: 250px;
                font-weight: 600;
                line-height: 250px;
                text-align: center;
                border-radius: 50%;
                background-color: #06bb68!important;
                display: inline-block;
                color: #ffffff;
                margin: 10px;'>$dezena</span>";
            }, $data['listaDezenas']);
            
            // Transformando as dezenas em uma string separada por espaços
            $dezenasHtml = implode(' ', $dezenas);
            
            // Criando a informação sobre o prêmio acumulado
            $estimado = $data['valorEstimadoProximoConcurso'] ?? 'Não estimado';
            $valorAcumuladoHtml = "<div style='text-align: center; margin-top: 20px;'>
                <h2 style='font-size: 40px;text-align: center; line-height:1; font-weight: 600; text-transform: uppercase;'>Estimativa do próximo concurso:</h1>
                <p style='font-size: 100px;text-align: center; line-height:1; font-weight: 600; margin-top: 0;'> R$ " . number_format($estimado, 2, ',', '.') . "</p>
            </div>";
            
            // Adicionando os dados ao provider
            $dataProvider->addItem([
                'subject' => 'Número do Concurso - ' . $numeroConcurso,
                'body' => $dezenasHtml . $valorAcumuladoHtml,  // Concatenando as dezenas com os dados do prêmio
                'date' => Carbon::now(),
                'createdAt' => Carbon::now(),
            ]);
        }

        // Marcando que os dados foram processados
        $dataProvider->setIsHandled();
        
        return $this;
    }

    public function fetchDuration(DurationProviderInterface $durationProvider): WidgetProviderInterface
    {
        return $this;
    }

    public function getDataCacheKey(DataProviderInterface $dataProvider): ?string
    {
        return null;
    }

    public function getDataModifiedDt(DataProviderInterface $dataProvider): ?Carbon
    {
        return null;
    }
}
