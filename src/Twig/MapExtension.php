<?php

namespace App\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MapExtension extends AbstractExtension
{
    public function __construct(
        private Environment $twig
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'render_map',
                [$this, 'renderMap'],
                ['is_safe' => ['html']] // Permet de retourner du HTML sans échapper
            ),
        ];
    }

    /**
     * Renders the map component.
     */
    public function renderMap(string $id, array $chartData, array $options = []): string
    {
        return $this->twig->render('components/map.html.twig', [
            'id'         => $id,
            'chart_data' => $chartData,
            'color_data' => $this->generate_color_map(data: $chartData, mode: "number"),
            'options'    => $options,
        ]);
    }

    private function generate_color_map($data, $start_hsl = [120, 60, 85], $end_hsl = [120, 100, 25], $mode = "pourcent"): array
    {
        $values = array_filter(array_values($data), fn ($v) => is_numeric($v));
        
        $min_val = min($values);
        $max_val = max($values);

        $color_map = [];

        foreach ($data as $key => $value) {

            $t = ($max_val != $min_val) ? ($value - $min_val) / ($max_val - $min_val) : 0;

            // Interpolation HSL
            $h = $start_hsl[0] + $t * ($end_hsl[0] - $start_hsl[0]);
            $s = $start_hsl[1] + $t * ($end_hsl[1] - $start_hsl[1]);
            $l = $start_hsl[2] + $t * ($end_hsl[2] - $start_hsl[2]);

            $color_map[$key] = "hsl(" . round($h) . ", " . round($s, 1) . "%, " . round($l, 1) . "%)";
        }

        $color_map["start_hsl"] = $start_hsl;
        $color_map["end_hsl"] = $end_hsl;
        $color_map["mode"] = $mode;

        return $color_map;
    }
}
