<?php

namespace App\Support;

/**
 * Minimal line-based diff (LCS) for the knowledge base version view —
 * article bodies are short enough that O(n*m) is fine.
 */
class LineDiff
{
    /**
     * @return array<int, array{type: 'same'|'added'|'removed', line: string}>
     */
    public static function compare(string $old, string $new): array
    {
        $a = preg_split('/\R/', $old);
        $b = preg_split('/\R/', $new);
        $lcs = self::lcsTable($a, $b);

        $result = [];
        $i = 0;
        $j = 0;

        while ($i < count($a) || $j < count($b)) {
            if ($i < count($a) && $j < count($b) && $a[$i] === $b[$j]) {
                $result[] = ['type' => 'same', 'line' => $a[$i++]];
                $j++;
            } elseif ($j < count($b) && ($i >= count($a) || $lcs[$i][$j + 1] >= $lcs[$i + 1][$j])) {
                $result[] = ['type' => 'added', 'line' => $b[$j++]];
            } else {
                $result[] = ['type' => 'removed', 'line' => $a[$i++]];
            }
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $a
     * @param  array<int, string>  $b
     * @return array<int, array<int, int>> $table[i][j] = LCS length of a[i..] and b[j..]
     */
    private static function lcsTable(array $a, array $b): array
    {
        $table = array_fill(0, count($a) + 1, array_fill(0, count($b) + 1, 0));

        for ($i = count($a) - 1; $i >= 0; $i--) {
            for ($j = count($b) - 1; $j >= 0; $j--) {
                $table[$i][$j] = $a[$i] === $b[$j]
                    ? $table[$i + 1][$j + 1] + 1
                    : max($table[$i + 1][$j], $table[$i][$j + 1]);
            }
        }

        return $table;
    }
}
