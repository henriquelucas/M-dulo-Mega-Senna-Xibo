<?php
namespace Xibo\Custom;

use Carbon\Carbon;
use Xibo\Widget\Provider\DataProviderInterface;
use Xibo\Widget\Provider\DurationProviderInterface;
use Xibo\Widget\Provider\WidgetProviderInterface;
use Xibo\Widget\Provider\WidgetProviderTrait;

class MegaSenaDataProvider implements WidgetProviderInterface
{
    use WidgetProviderTrait;

    public function fetchData(DataProviderInterface $dataProvider): WidgetProviderInterface
    {
        $url = 'https://api.guidi.dev.br/loteria/megasena/ultimo';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \Exception("Erro ao acessar a API da Mega Sena.");
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new \Exception("Erro ao decodificar o JSON da API ou formato inválido.");
        }

        // Extração segura dos dados da API
        $drawDate = $data['dataApuracao'] ?? 'N/D';
        $drawNumbers = $data['listaDezenas'] ?? [];
        $prizeDetails = $data['listaRateioPremio'] ?? [];
        $nextDrawDate = $data['dataProximoConcurso'] ?? 'N/D';
        $accumulatedPrize = $data['valorAcumuladoProximoConcurso'] ?? 0;
        $estimativaproximo = $data['valorEstimadoProximoConcurso'] ?? 'N/D';
        $numeroconcurso = $data['numeroConcursoFinal_0_5'];

        // Gere o HTML para os números sorteados
        $drawNumbersHtml = implode(' ', array_map(function ($number) {
            return "<span class='numero'>{$number}</span>";
        }, $drawNumbers));

        // Gere o HTML para as premiações
        $prizeHtml = '';
        foreach ($prizeDetails as $prize) {
            $prizeHtml .= "<div class='premiacao'>";
            $prizeHtml .= "<span class='descricao'>{$prize['descricaoFaixa']}</span>: ";
            $prizeHtml .= "<span class='ganhadores'>{$prize['numeroDeGanhadores']} ganhadores</span>, ";
            $prizeHtml .= "<span class='valorPremio'>R$ " . number_format($prize['valorPremio'], 2, ',', '.') . "</span>";
            $prizeHtml .= "</div>";
        }

        // Adicione os dados ao provedor
        $dataProvider->addItem([
            'draw_date' => $drawDate,
            'draw_numbers' => $drawNumbersHtml,
            'prize_details' => $prizeHtml,
            'next_draw_date' => $nextDrawDate,
            'numero_concurso' => $numeroconcurso,
            'accumulated_prize' => "R$ " . number_format($accumulatedPrize, 2, ',', '.'),
            'estimativa_proximo' => "R$ " . number_format($estimativaproximo, 2, ',', '.'),
        ]);

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
