<?php
// core/FotoUtil.php
// Utilidad compartida para leer el campo `foto` de recetas (JSON con 1+ URLs),
// tolerando datos corruptos de guardados anteriores (JSON anidado / doble-encoded).

class FotoUtil {

    /**
     * Recibe el valor crudo del campo `foto` (o un array/valor ya decodificado)
     * y devuelve siempre un array plano de URLs limpias.
     */
    public static function parseFotoUrls($raw): array {
        if (is_array($raw)) {
            $flat = [];
            foreach ($raw as $item) {
                foreach (self::parseFotoUrls($item) as $u) $flat[] = $u;
            }
            return array_values(array_unique(array_filter($flat, 'strlen')));
        }

        $raw = trim((string)$raw);
        if ($raw === '') return [];

        // Si parece JSON (array u objeto/string codificado), intenta decodificarlo
        // y aplanar recursivamente por si quedó anidado por un guardado anterior.
        if ($raw[0] === '[' || $raw[0] === '"') {
            $decoded = json_decode($raw, true);
            if ($decoded !== null) {
                return self::parseFotoUrls($decoded);
            }
        }

        return [$raw];
    }

    /** Devuelve solo la primera URL (la que se usa como ícono/portada), o null si no hay ninguna. */
    public static function primeraFoto($raw): ?string {
        $urls = self::parseFotoUrls($raw);
        return $urls[0] ?? null;
    }
}
