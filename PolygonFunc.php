<?php
/**
 * Created by PhpStorm.
 * User: Бахвалов.Владислав
 * Date: 05.12.2018
 * Time: 10:40
 */

/** Класс для работы с полигонами */
class PolygonFunc
{
    public const EARTH_RADIUS = 6371000; //Радиус земли в метрах
    public const OFFSET_ANGLE_X = 0; //Смещение в градусах по х (Долгота)
    public const OFFSET_ANGLE_Y = 30; //Смещение в градусах по у (Широта) ~30 градусов - Петербург

    /**
     * Проверяет пересечение двух отрезков.
     *
     * @param float $aLineX1 Первая координата X первого отрезка.
     * @param float $aLineY1 Первая координата Y первого отрезка.
     * @param float $aLineX2 Вторая координата X первого отрезка.
     * @param float $aLineY2 Вторая координата Y первого отрезка.
     * @param float $bLineX1 Первая координата X второго отрезка.
     * @param float $bLineY1 Первая координата Y второго отрезка.
     * @param float $bLineX2 Вторая координата X второго отрезка.
     * @param float $bLineY2 Вторая координата Y второго отрезка.
     * @return bool Возвращает true если отрезки пересекаются.
     */
    public static function linesCross(
        float $aLineX1,
        float $aLineY1,
        float $aLineX2,
        float $aLineY2,
        float $bLineX1,
        float $bLineY1,
        float $bLineX2,
        float $bLineY2
    ): bool
    {

        $aLineX1 = self::projectFromGEOToPlane($aLineX1, self::OFFSET_ANGLE_X);
        $aLineX2 = self::projectFromGEOToPlane($aLineX2, self::OFFSET_ANGLE_X);

        $bLineX1 = self::projectFromGEOToPlane($bLineX1, self::OFFSET_ANGLE_X);
        $bLineX2 = self::projectFromGEOToPlane($bLineX2, self::OFFSET_ANGLE_X);


        $aLineY1 = self::projectFromGEOToPlane($aLineY1, self::OFFSET_ANGLE_Y);
        $aLineY2 = self::projectFromGEOToPlane($aLineY2, self::OFFSET_ANGLE_Y);

        $bLineY1 = self::projectFromGEOToPlane($bLineY1, self::OFFSET_ANGLE_Y);
        $bLineY2 = self::projectFromGEOToPlane($bLineY2, self::OFFSET_ANGLE_Y);

        $d = ($aLineX2 - $aLineX1) * ($bLineY1 - $bLineY2) - ($bLineX1 - $bLineX2) * ($aLineY2 - $aLineY1);

        // Отрезки параллельны.
        if ($d === 0.0) {
            return false;
        }

        $d1 = ($bLineX1 - $aLineX1) * ($bLineY1 - $bLineY2) - ($bLineX1 - $bLineX2) * ($bLineY1 - $aLineY1);
        $d2 = ($aLineX2 - $aLineX1) * ($bLineY1 - $aLineY1) - ($bLineX1 - $aLineX1) * ($aLineY2 - $aLineY1);

        $t1 = $d1 / $d;
        $t2 = $d2 / $d;

        return ($t1 >= 0 && $t1 <= 1 && $t2 >= 0 && $t2 <= 1);
    }

    /**
     * Переводит радианы в градусы.
     *
     * @param float $aRadians Угол в радианах.
     * @return float Возвращает угол в градусах.
     */
    public static function toDegrees(float $aRadians): float
    {
        return $aRadians * 180 / M_PI;
    }

    /**
     * Переводит градусы в радианы
     *
     * @param float $aDegrees Угол в градусах.
     * @return float Возвращает угол в радианах.
     */
    public static function toRadians(float $aDegrees): float
    {
        return $aDegrees * M_PI / 180;
    }

    /**
     * Принадлежность точки к полигону
     *
     * @param array $polyCords Полигон
     * @param float $pointX Координата X точки
     * @param float $pointY Координата Y точки
     * @return bool Возвращает входит/не входит точка в полигон
     */
    public static function pointInPoly(array $polyCords, float $pointX, float $pointY): bool
    {
        $c = false;
        $polySize = count($polyCords);

        for ($i = 0, $j = $polySize - 1; $i < $polySize; $j = $i++) {

            $moreThenX = ($polyCords[$j][0] - $polyCords[$i][0]) * ($pointY - $polyCords[$i][1]) /
                ($polyCords[$j][1] - $polyCords[$i][1]) + $polyCords[$i][0];

            if (($pointX < $moreThenX) && (($polyCords[$i][1] > $pointY) !== ($polyCords[$j][1] > $pointY))) {
                $c = !$c;
            }
        }

        return $c;
    }

    /**
     * парсит JSON и отдает массив с координатами
     *
     * @param string $json JSON в строчном формате
     * @return array Возвращает массив с координатами полигона
     * @throws Exception
     */
    public static function parsePolyJSON(string $json): array
    {
        try {
            $tmpJSON = (string)$json[strlen($json) - 2] === ',' ? substr($json, 0, -2) . ']' : $json;
            return json_decode(html_entity_decode(html_entity_decode($tmpJSON)), true)[0][0];
        } catch (Exception $exception) {
            throw $exception;
        }
    }


    /**
     * Перевод из ширины/долготы в пиксели (ДЛЯ ДЕБАГА!)
     *
     * @param array $params Массив с координатами
     * @param float $minX1 Минимальная точка по оси X
     * @param float $maxY2 Максимальная точка по оси Y
     * @param float $scalarTwo Скаляр масштаба
     * @return array
     */
    public static function convertFromGISToPixels(
        array $params,
        float $minX1,
        float $maxY2,
        float $scalarTwo = 0.25
    ): array
    {
        $scalarOne = 7000000;
        $fill[] = (($params[1] - $minX1) * $scalarOne + 4000) * $scalarTwo;
        $fill[] = (($maxY2 - $params[0]) * $scalarOne + 4000) * $scalarTwo;

        return $fill;
    }

    /**
     * Площадь полигона на поверхности земли с гео-координатами из яндекса
     *
     * @param array $poly Массив с точками полигона
     * @return float Возваращает площадь
     */
    public static function calculatePolygonS(array $poly): float
    {
        $area = 0;
        $scalarRad = 0.0174533; // Скаляр для перевода из градусов в радианы
        $doubleEarthR = 20340315795384; // радиус земли * 2
        foreach ($poly as $i => $point) {
            if (empty($poly[$i + 1])) {
                break;
            }
            $p1 = $poly[$i];
            $p2 = $poly[$i + 1];
            $area += (($p2[1] - $p1[1]) * $scalarRad) * (2 + sin($p1[0] * $scalarRad) + sin($p2[0] * $scalarRad));
        }

        return abs($area) * $doubleEarthR;
    }

    /**
     * Проверяет пресекаются ли полигоны (без проверки вхождения одного в другого)
     *
     * @param array $first Массив с точками первого полигона
     * @param array $second Массив с точками второго полигона
     * @return bool
     */
    public static function ifItsCross(array $first, array $second): bool
    {
        $first = self::orientation($first) ? array_reverse($first) : $first;
        $second = self::orientation($second) ? array_reverse($second) : $second;

        foreach ($second as $point) {
            if (self::pointInPoly($first, $point[0], $point[1])) {
                return true;
            }
        }

        $firstCount = count($first);
        $secondCount = count($second);
        for ($i = 0; $i < $firstCount; $i += 2) {
            if (!isset($first[$i + 1])) {
                break;
            }
            for ($j = 0; $j < $secondCount; $j += 2) {
                if (!isset($second[$j + 1])) {
                    break;
                }
                if (self::linesCross(
                    $first[$i][0], $first[$i][1],
                    $first[$i + 1][0], $first[$i + 1][1],
                    $second[$j][0], $second[$j][1],
                    $second[$j + 1][0], $second[$j + 1][1]
                )) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Найти максимальные и максимальные координаты
     *
     * @param array ...$param Скольугодно много массивов с координатами
     * @return array Возвращает масиив с минимальными и максимальными координатами по X и Y
     */
    public static function getMaxAndMin(array ...$param): array
    {
        $maxX = $minX = $param[0][0][0];
        $maxY = $minY = $param[0][0][1];

        foreach ($param as $item) {
            foreach ($item as $value) {

                $maxX = ($maxX > $value[0]) ? $maxX : $value[0];
                $maxY = ($maxY > $value[1]) ? $maxY : $value[1];
                $minX = ($minX < $value[0]) ? $minX : $value[0];
                $minY = ($maxY < $value[1]) ? $minY : $value[1];
            }
        }

        return ['min' => [$minX, $minY], 'max' => [$maxX, $maxY]];
    }

    /**
     * Рисует полигон
     *
     * @param array $poly Массив с точками полигона
     * @param array $maxmin Максимальные/минимальные точки полигона
     * @param int $width Ширина изображения
     * @param int $height Высота изображения
     * @param array $bgColor Цвет фона
     * @param array $polyColor Цвет полигона
     * @param null $img Изображение, если нужно нарисовать на уже имеющемся
     * @return null|resource Вернет изображение
     */
    public static function drawOnImg(
        array $poly,
        array $maxmin,
        int $width = 3000,
        int $height = 3000,
        array $bgColor = [0, 0, 0],
        array $polyColor = [0, 0, 255],
        $img = null
    )
    {

        $testP1 = [];
        foreach ($poly as $li) {
            $xy = self::convertFromGISToPixels($li, $maxmin['min'][1], $maxmin['max'][0]);
            $testP1[] = $xy[0];
            $testP1[] = $xy[1];
        }

        // создание изображения
        $image = $img ?? imagecreatetruecolor($width, $height);

        // определение цветов
        $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
        $blue = imagecolorallocatealpha($image, $polyColor[0], $polyColor[1], $polyColor[2], 90);

        // заливка фона
        if ($img === null) {
            imagefilledrectangle($image, 0, 0, $width, $height, $bg);
        }

        // рисование многоугольника
        imagefilledpolygon($image, $testP1, count($testP1) / 2, $blue);

        return $image;
    }

    /**
     * Рисует сколь угодно много полигонов на картинке и отдает ее браузеру (используется для дебага!)
     *
     * @param int $width Ширина картинки
     * @param int $height Высота картинки
     * @param array ...$poly Массивы с полигонами
     * @throws Exception
     */
    public static function renderPolygons(int $width, int $height, array ...$poly): void
    {
        $maxmin = self::getMaxAndMin(...$poly);

        $bgColor = [255, 255, 255];
        //$polyColor = [random_int(0, 255), random_int(0, 255), random_int(0, 255)];
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);
        foreach ($poly as $pol) {
            $image = self::drawOnImg($pol, $maxmin, $width, $height, $bgColor,
                [random_int(0, 255), random_int(0, 255), random_int(0, 255)], $image);
        }

        header('Content-type: image/png');
        imagepng($image);
        imagedestroy($image);
    }

    /**
     *  Рассчитывает процент исходя из текущего и общего значения.
     *
     * @param float $aCurrent Текущее значание.
     * @param float $aTotal Общее значение.
     * @return float Возвращает процент текущего значения.
     */
    public static function toPercent(float $aCurrent, float $aTotal): float
    {
        return ($aCurrent / $aTotal) * 100;
    }

    /**
     * Рассчитывает текущее значение исходя из текущего процента и общего значения.
     *
     * @param float $aPercent Текущий процент.
     * @param float $aTotal Общее значение.
     * @return float Возвращает текущее значение.
     */
    public static function fromPercent(float $aPercent, float $aTotal): float
    {
        return ($aPercent * $aTotal) / 100;
    }

    /**
     * Рассчитывает дистанцию между указанными точками.
     *
     * @param float $aX1 Координата X первой точки.
     * @param float $aY1 Координата Y первой точки.
     * @param float $aX2 Координата X второй точки.
     * @param float $aY2 Коордианат Y второй точки.
     * @return float Возрвщает дистанцию между точками.
     */
    public static function distance(float $aX1, float $aY1, float $aX2, float $aY2): float
    {
        $dx = $aX2 - $aX1;
        $dy = $aY2 - $aY1;
        return sqrt($dx * $dx + $dy * $dy);
    }

    /**
     * Рассчитывает угол между двумя точками в радианах.
     *
     * @param float $aX1 Координата X первой точки.
     * @param float $aY1 Координата Y первой точки.
     * @param float $aX2 Координата X второй точки.
     * @param float $aY2 Коордианат Y второй точки.
     * @param bool $aNorm Если true, то угол будет нормализован.
     * @return float Возвращает угол между двумя точками в радианах.
     */
    public static function angle(float $aX1, float $aY1, float $aX2, float $aY2, bool $aNorm = true): float
    {
        $dx = $aX2 - $aX1;
        $dy = $aY2 - $aY1;
        $angle = atan2($dy, $dx);
        return $aNorm ? self::normAngle($angle) : $angle;
    }

    /**
     * Рассчитывает угол между двумя точками в градусах.
     *
     * @param float $aX1 Координата X первой точки.
     * @param float $aY1 Координата Y первой точки.
     * @param  float $aX2 Координата X второй точки.
     * @param float $aY2 Коордианат Y второй точки.
     * @param bool $aNorm Если true, то угол будет нормализован.
     * @return float Возвращает угол между двумя точками в градусах.
     */
    public static function angleDeg(float $aX1, float $aY1, float $aX2, float $aY2, bool $aNorm = true): float
    {
        $dx = $aX2 - $aX1;
        $dy = $aY2 - $aY1;
        $angle = atan2($dy, $dx) / M_PI * 180;
        return $aNorm ? self::normAngleDeg($angle) : $angle;
    }

    /**
     * Нормализирует угол в градусах.
     *
     * @param float $aAngle Угол в градусах который необходимо нормализировать.
     * @return float Возвращает нормализированный угол в градусах.
     */
    public static function normAngleDeg(float $aAngle): float
    {
        $first = ($aAngle >= 360) ? $aAngle - 360 : $aAngle;
        return ($aAngle < 0) ? 360 + $aAngle : $first;
    }

    /**
     * Нормализирует угол в радианах.
     *
     * @param float $aAngle Угол в радианах который необходимо нормализировать.
     * @return float Возвращает нормализированный угол в радианах.
     */
    public static function normAngle(float $aAngle): float
    {
        $first = ($aAngle >= M_PI * 2) ? $aAngle - M_PI * 2 : $aAngle;
        return ($aAngle < 0) ? M_PI * 2 + $aAngle : $first;
    }

    /**
     * Проверяет если полигон $second внутри полигона $first
     *
     * @param array $first
     * @param array $second
     * @return bool
     */
    public static function ifOnePolyInsideAnother(array $first, array $second): bool
    {
        if (self::checkPolyEqual($first, $second)) {
            return true;
        }

        $first = self::orientation($first) ? array_reverse($first) : $first;
        $second = self::orientation($second) ? array_reverse($second) : $second;

        /**
         * Алгоритм такой:
         * Проверяем если все точки одного полигона внутри второго, если это так, то
         * проверяем на пересечение ребер полигона, если они не пересекаются, то $second полигон точно внутри $first полигона
         */
        foreach ($second as $point) {
            if (!self::pointInPoly($first, $point[0], $point[1])) {
                return false;
            }
        }
        $firstCount = count($first);
        $secondCount = count($second);
        for ($i = 0; $i < $firstCount; $i += 2) {
            if (!isset($first[$i + 1])) {
                break;
            }
            for ($j = 0; $j < $secondCount; $j += 2) {
                if (!isset($second[$j + 1])) {
                    break;
                }
                if (self::linesCross(
                    $first[$i][0], $first[$i][1],
                    $first[$i + 1][0], $first[$i + 1][1],
                    $second[$j][0], $second[$j][1],
                    $second[$j + 1][0], $second[$j + 1][1]
                )) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Проверяет на идентичность полигонов
     *
     * @param array $p1 Первый полигон
     * @param array $p2 Второй полигон
     * @return bool Возвращает true/false в зависимости от идентичности полигонов
     */
    public static function checkPolyEqual(array $p1, array $p2): bool
    {
        if (empty($p1) || empty($p2)) {
            return false;
        }
        $k = 5;//кол-во знаков после запятой
        foreach ($p1 as $key => $item) {
            $p1[$key] = number_format($item[0], $k) . ';' . number_format($item[1], $k);
        }
        foreach ($p2 as $key => $item) {
            $p2[$key] = number_format($item[0], $k) . ';' . number_format($item[1], $k);
        }
        return count(array_diff($p1, $p2)) === 0;
    }

    /**
     * Вернет координаты середины полигона
     *
     * @param array $polygon Массив с точками полигона
     * @return array Возвращает массив с токами центра полигона [x,y]
     */
    public static function getCenterOfPolygon(array $polygon): array
    {
        $averageX = 0;
        $averageY = 0;
        $total = count($polygon);
        foreach ($polygon as $point) {
            $averageX += $point[0];
            $averageY += $point[1];
        }

        return [$averageX / $total, $averageY / $total];
    }

    /**
     * Функция для масштабирования полигона
     *
     * @param array $polygon Полигон
     * @param float $scalar Скаляр на который полигон будет увеличен/уменьшен
     * @return array Возвращает отмастштабированный полигон
     */
    public static function scalePolygon(array $polygon, float $scalar): array
    {
        $center = self::getCenterOfPolygon($polygon);

        return array_map(static function ($val) use ($center, $scalar) {
            $xt = $scalar * $val[0] + (1 - $scalar) * $center[0];
            $yt = $scalar * $val[1] + (1 - $scalar) * $center[1];
            return [$xt, $yt];
        }, $polygon);
    }

    /**
     *  Масштабирует полигон до новой заданной площадт
     * @param array $polygon Полигон, который нужно масштабировать
     * @param float $newS Новая площадь, до которой нужно масштабировать полигон
     * @return array Полигон с новой площадью
     */
    public static function scalePolygonToNewS(array $polygon, float $newS): array
    {
        $pogr = 0.00000001; //Это коэффициент погрешности при расчете скаляра
        $currenS = self::calculatePolygonS($polygon); //Найдем текущую площадь
        $scalar = sqrt($newS / $currenS) + $pogr; //Найдем скаляр на который нужно будет увеличить новый полигон

        return self::scalePolygon($polygon, $scalar);
    }

    /**
     * Проверят пересечение двух отрезков и рассчитывает точку пересечения.
     *
     * @param float $aLineX1 Первая координата X первого отрезка.
     * @param float $aLineY1 Первая координата Y первого отрезка.
     * @param float $aLineX2 Вторая координата X первого отрезка.
     * @param float $aLineY2 Вторая координата Y первого отрезка.
     * @param float $bLineX1 Первая координата X второго отрезка.
     * @param float $bLineY1 Первая координата Y второго отрезка.
     * @param float $bLineX2 Вторая координата X второго отрезка.
     * @param float $bLineY2 Вторая координата Y второго отрезка.
     *
     * @return array|bool Вернет false если отрезки не пересекаются иначе точку в которую будут записаны координаты персечения отрезков.
     */
    public static function linesCrossPoint(
        float $aLineX1,
        float $aLineY1,
        float $aLineX2,
        float $aLineY2,
        float $bLineX1,
        float $bLineY1,
        float $bLineX2,
        float $bLineY2
    )
    {
        //Перевеведм координаты из градусов на плоскость
        $aLineX1 = self::projectFromGEOToPlane($aLineX1, self::OFFSET_ANGLE_X);
        $aLineX2 = self::projectFromGEOToPlane($aLineX2, self::OFFSET_ANGLE_X);

        $bLineX1 = self::projectFromGEOToPlane($bLineX1, self::OFFSET_ANGLE_X);
        $bLineX2 = self::projectFromGEOToPlane($bLineX2, self::OFFSET_ANGLE_X);


        $aLineY1 = self::projectFromGEOToPlane($aLineY1, self::OFFSET_ANGLE_Y);
        $aLineY2 = self::projectFromGEOToPlane($aLineY2, self::OFFSET_ANGLE_Y);

        $bLineY1 = self::projectFromGEOToPlane($bLineY1, self::OFFSET_ANGLE_Y);
        $bLineY2 = self::projectFromGEOToPlane($bLineY2, self::OFFSET_ANGLE_Y);


        $aResultPoint = [];

        $d = ($aLineX2 - $aLineX1) * ($bLineY1 - $bLineY2) - ($bLineX1 - $bLineX2) * ($aLineY2 - $aLineY1);

        // Отрезки паралельны.
        if ($d === 0.0) {
            return false;
        }

        $d1 = ($bLineX1 - $aLineX1) * ($bLineY1 - $bLineY2) - ($bLineX1 - $bLineX2) * ($bLineY1 - $aLineY1);
        $d2 = ($aLineX2 - $aLineX1) * ($bLineY1 - $aLineY1) - ($bLineX1 - $aLineX1) * ($aLineY2 - $aLineY1);

        $t1 = $d1 / $d;
        $t2 = $d2 / $d;

        if ($t1 >= 0 && $t1 <= 1 && $t2 >= 0 && $t2 <= 1) {
            $aResultPoint[0] = self::projectFromPlaneToGEO($aLineX1 + ($t1 * ($aLineX2 - $aLineX1)),
                self::OFFSET_ANGLE_X);
            $aResultPoint[1] = self::projectFromPlaneToGEO($aLineY1 + ($t1 * ($aLineY2 - $aLineY1)),
                self::OFFSET_ANGLE_Y);

            return $aResultPoint;
        }
        return false;
    }

    /**
     * Спроецировать координаты на плоскость
     *
     * @param float $point Угол (Долгота/ширина)
     * @param float $angle Угол смещения (Для СПб ~30)
     * @return float Возвращает координату по оси (х или у)
     */
    public static function projectFromGEOToPlane(float $point, float $angle): float
    {
        return self::EARTH_RADIUS * sin($point - $angle);
    }

    /**
     * Перевести из координат плоскости в GEO координаты
     *
     * @param float $point Координата по оси (х или у)
     * @param float $angle Угол смещения
     * @return float Возвращает угол координаты
     */
    public static function projectFromPlaneToGEO(float $point, float $angle): float
    {
        return $angle + asin($point / self::EARTH_RADIUS);
    }

    /**
     * Функция для проверки направления обхода полигона
     *
     * @param array $poly
     * @return bool Возвращает true противчасовой стрелке, false по часовой
     */
    public static function orientation(array $poly): bool
    {
        $S = 0.0;
        $n = count($poly);
        $n = $n > 8 ? 8 : $n;
        for ($i = 0; $i < $n; ++$i) {
            $S += $poly[$i][0] * ($poly[($i + 1) % $n][1] - $poly[($i + $n - 1) % $n][1]);
        }
        $S /= 2.0;

        return ((int)(0.0 < $S) - (int)($S < 0.0)) > 0;
    }

    /**
     * Объединение двух полигонов (TODO нужен рефакторинг (+ вариант, когда у нас группа полигонов))
     *
     * @param array $first Первый полигон (родительский)
     * @param array $second Второй полигон (дочерний)
     * @return array
     */
    public static function clipPolygon(array $first, array $second): array
    {
        if (self::checkPolyEqual($first, $second)) {
            return $first;
        }

        $first = self::orientation($first) ? array_reverse($first) : $first;
        $second = self::orientation($second) ? array_reverse($second) : $second;

        $firstCount = count($first) - 1;
        $secondCount = count($second) - 1;

        $firstIndex = 0;
        $secondIndex = 0;
        $out = [];
        $currentPoint = $second[0];
        $switch = false;
        while ($secondIndex < $secondCount) {
            if (!$switch) { //Идем по дочернему полигону
                if (self::pointInPoly($first, $second[$secondIndex][0], $second[$secondIndex][1])) {
                    $out [] = [
                        $second[$secondIndex][0],
                        $second[$secondIndex][1]
                    ]; //Если точка входит родительский полигон, добавляем ее
                    $currentPoint = $second[$secondIndex];
                }

                //Проверяем на пересечение линий дочерних с родительскими
                for ($j = 0; $j < $firstCount; $j++) {
                    if (!isset($first[$j + 1])) {
                        break;
                    }
                    $r = self::linesCrossPoint(
                        $currentPoint[0],
                        $currentPoint[1],

                        $second[$secondIndex + 1][0],
                        $second[$secondIndex + 1][1],

                        $first[$j][0],
                        $first[$j][1],

                        $first[$j + 1][0],
                        $first[$j + 1][1]
                    );
                    if ($r !== false) {
                        //Нашли точку пересечения, добавляем ее
                        $out [] = $r;
                        $currentPoint = $r;
                        $firstIndex = $j;
                        $switch = true; // Переключаемся на родительский полигон
                        break;
                    }
                }
                $secondIndex++;
            } else { //Идем по родительскому полигону
                if (self::pointInPoly($second, $first[$firstIndex][0], $first[$firstIndex][1])) {
                    $out [] = [
                        $first[$firstIndex][0],
                        $first[$firstIndex][1]
                    ]; //Если точка входит дочерний полигон, добавляем ее
                    $currentPoint = $first[$firstIndex];
                }
                if (!isset($first[$firstIndex + 1])) {
                    break;
                }
                //Проверяем на пересечение линий родительских с дочерними
                for ($j = 0; $j < $secondCount; $j++) {
                    if (!isset($second[$j + 1])) {
                        break;
                    }
                    $r = self::linesCrossPoint(
                        $currentPoint[0],
                        $currentPoint[1],

                        $first[$firstIndex + 1][0],
                        $first[$firstIndex + 1][1],

                        $second[$j][0],
                        $second[$j][1],

                        $second[$j + 1][0],
                        $second[$j + 1][1]
                    );
                    if ($r !== false) {
                        //Нашли точку пересечения, добавляем ее
                        $out [] = $r;
                        $currentPoint = $r;
                        $switch = false; // Переключаемся на дочерний полигон
                        break;
                    }
                }
                $firstIndex++;
            }
        }

        return $out;
    }

    /**
     * Проверка на равенство отрезков
     *
     * @param array $a Массив с точками первого отрезка
     * @param array $b Массив с точками второго отрезка
     * @return bool Вернет True если равны, False если не равны
     */
    public static function cmp(array $a, array $b): bool
    {
        return $a[0] === $b[0] && $a[1] === $b[1];
    }

    /**
     * Проверка на самопересечение
     *
     * @param array $poly Массив с точками полигона
     * @return bool Вернет true если есть самопресечение, вернет false если нет самопересечений
     */
    public static function selfIntersections(array $poly): bool
    {
        $poly = self::orientation($poly) ? array_reverse($poly) : $poly;
        $l = count($poly);
        for ($o = 0; $o < $l; $o++) {
            $oc = $poly[$o];
            $on = $poly[($o + 1) % $l];

            for ($p = 0; $p < $l; $p++) {
                if ($o === $p) {
                    continue;
                }
                $pc = $poly[$p];
                $pn = $poly[($p + 1) % $l];

                if (self::cmp($pc, $oc) || self::cmp($pc, $on) || self::cmp($pn, $oc) || self::cmp($pn, $on)) {
                    continue;
                }
                $r = self::linesCrossPoint(
                    $oc[0],
                    $oc[1],
                    $on[0],
                    $on[1],
                    $pc[0],
                    $pc[1],
                    $pn[0],
                    $pn[1]
                );

                if ($r !== false) {
                    return true;
                }
            }
        }
        return false;
    }


    /**
     * Вернет точку середины отрезка
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return array
     */
    public static function middleLinePoint(float $x1, float $y1, float $x2, float $y2): array
    {
        return [
            $x1 + (($x2 - $x1) / 2),
            $y1 + (($y2 - $y1) / 2),
        ];
    }
}