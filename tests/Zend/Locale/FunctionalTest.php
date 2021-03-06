<?php

class Zend_Locale_FunctionalTest extends PHPUnit_Framework_TestCase
{
    function setUp()
    {
        Zend_Locale::disableCache(true);
    }

    function tearDown()
    {
        Zend_Locale::disableCache(false);
    }

    function localeFormats()
    {
        return [
            ['ar_AE', '05/04/2015', 'د.إ.‏ 1.234,56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['ar_QA', '05-Apr-2015', 'ر.ق.‏ 1.234,56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],

            ['be_BY', '05/04/2015', '1.234,56 р.', 'нядзеля', 'няд', 'н', 'красавіка', 'кра'],
            ['bg_BG', '05/04/2015', '1 234,56 лв.', 'неделя', 'нед', 'н', 'април', 'апр.'],

            ['cs_CZ', '05/04/2015', '1 234,56 Kč', 'neděle', 'ned', 'n', 'dubna', 'dub'],

            ['de_DE', '05.04.2015', '1.234,56 €', 'Sonntag', 'Son', 'S', 'April', 'Apr.'],
            ['da_DK', '05/04/2015', '1.234,56 DKK', 'søndag', 'søn', 's', 'april', 'apr.'],

            ['el_GR', '05/04/2015', '1.234,56 €', 'Κυριακή', 'Κυρ', 'Κ', 'Απριλίου', 'Απρ'],

            ['en_GB', '05/04/2015', '£1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_IE', '05/04/2015', '€1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_US', '04/05/2015', '$1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_NZ', '05/04/2015', '$1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_ZA', '05/04/2015', 'R1 234,56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_IN', '05/04/2015', '₹ 1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_HK', '05/04/2015', '$1,234.56', '星期日', '星期日', '周', '四月', '4月'],
            ['en_SG', '05/04/2015', '$1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_PH', '05/04/2015', '₱1,234.56', 'Linggo', 'Lin', 'L', 'Abril', 'Abr'],
            ['en_CA', '05/04/2015', '$1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_AU', '05/04/2015', '$1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],
            ['en_TP', '05/04/2015', 'TPE1,234.56', 'Sunday', 'Sun', 'S', 'April', 'Apr'],

            ['es_ES', '05/04/2015', '1.234,56 €', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_MX', '05/04/2015', '1,234.56 $', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_AR', '05/04/2015', '1.234,56 $', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_CL', '05/04/2015', '$1.234,56', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_CO', '5/04/2015', '1.234,56 $', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_CR', '05/04/2015', '1.234,56 ₡', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_VE', '05/04/2015', 'Bs.1.234,56', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_BO', '05/04/2015', '1.234,56 Bs', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_EC', '05/04/2015', '$1.234,56', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_SV', '05/04/2015', '1,234.56 $', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_GT', '5/04/2015', '1,234.56 Q', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_HN', '05/04/2015', '1,234.56 L', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_NI', '05/04/2015', '1,234.56 C$', 'domingo', 'dom', 'd', 'abril', 'abr.'],
            ['es_PA', '05/04/2015', '1,234.56 B/.', 'domingo', 'dom', 'd', 'abril', 'abr.'],

            ['et_EE', '4/05/2015', '1 234,56 €', 'pühapäev', 'püh', 'P', 'aprill', 'apr'],

            ['fr_FR', '05/04/2015', '1 234,56 €', 'dimanche', 'dim', 'd', 'avril', 'avr.'],
            ['fi_FI', '05/04/2015', '1 234,56 €', 'sunnuntaina', 'sun', 's', 'huhtikuuta', 'huhtikuuta'],

            ['hu_HU', '2015.04.05.', '1 234,56 Ft', 'vasárnap', 'vas', 'V', 'április', 'ápr.'],
            ['he_IL', '05/04/2015', '1,234.56 ₪', 'יום ראשון', 'יום', 'י', 'אפריל', 'אפר׳'],

            ['it_IT', '05/04/2015', '€ 1.234,56', 'domenica', 'dom', 'd', 'aprile', 'apr'],
            ['id_ID', '05/04/2015', 'Rp1.234,56', 'Minggu', 'Min', 'M', 'April', 'Apr'],

            ['ja_JP', '05/04/2015', ['￥1,235', ['precision' => 0]], '日曜日', '日曜日', '日', '4月', '4月'],
            ['ko_KR', '05/04/2015', ['₩1,235', ['precision' => 0]], '일요일', '일요일', '일', '4월', '4월'],

            ['lt_LT', '05/04/2015', '€ 1.234,56', 'sekmadienis', 'sek', 's', 'balandis', 'bal.'],
            ['lv_LV', '05/04/2015', '€ 1.234,56', 'svētdiena', 'svē', 'S', 'aprīlis', 'apr.'],

            ['ms_MY', '4/5/2015', 'RM1,234.56', 'Ahad', 'Aha', 'A', 'April', 'Apr'],

            ['nb_NO', '05/04/2015', 'kr 1 234,56', 'søndag', 'søn', 's', 'april', 'apr.'],
            ['nl_NL', '05/04/2015', '€ 1.234,56', 'zondag', 'zon', 'z', 'april', 'apr.'],

            ['pl_PL', '05-04-2015', '1 234,56 zł', 'niedziela', 'nie', 'n', 'kwietnia', 'kwi'],
            ['pt_PT', '05/04/2015', '1 234,56 €', 'domingo', 'dom', 'd', 'Abril', 'Abr'],
            ['pt_BR', '05/04/2015', 'R$1.234,56', 'domingo', 'dom', 'd', 'abril', 'abr'],

            ['ro_RO', '05.04.2015', '1.234,56 RON', 'duminică', 'dum', 'D', 'aprilie', 'apr.'],
            ['ru_RU', '05/04/2015', '1 234,56 руб.', 'воскресенье', 'вос', 'в', 'апреля', 'апр.'],

            ['sr_RS', '5.4.2015.', 'din. 1.234,56', 'nedelja', 'ned', 'n', 'april', 'apr'],
            ['sl_SI', '05/04/2015', '€ 1.234,56',  'nedelja', 'ned', 'n', 'april', 'apr.'],
            ['sq_AL', '2015-04-05', 'Lekë1.234,56', 'e diel', 'e d', 'D', 'prill', 'Pri'],
            ['sv_SE', '05/04/2015', '1 234,56 kr', 'söndag', 'sön', 's', 'april', 'apr'],

            ['tr_TR', '05.04.2015', '1.234,56 ₺', 'Pazar', 'Paz', 'P', 'Nisan', 'Nis'],

            ['uk_UA', '05/04/2015', '1 234,56 ₴', 'неділя', 'нед', 'Н', 'квітня', 'квіт.'],

            ['vi_VN', '05/04/2015', ['1.234,560 ₫', ['precision' => 3]], 'Chủ Nhật', 'Chủ', 'C', 'tháng 4', 'thg 4'],

            ['zh_CN', '05/04/2015', '￥1,234.56', '星期日', '星期日', '周', '四月', '4月'],
            ['zh_TW', '05/04/2015', 'NT$1,234.56', '星期日', '星期日', '週', '4月', '4月'],
        ];
    }

    /**
     * @dataProvider localeFormats
     */
    function testlocale($locale, $shortDate, $amount, $weekday,
        $weekdayShort, $weekDayNarrow, $monthName, $monthNameShort)
    {
        $myDate = $this->dateShortFormatInLocale($locale);

        $this->assertEquals($shortDate, $myDate);
        $this->_testDateFormatParsing($myDate, $locale);

        $options = [];
        if (is_array($amount)) {
            list($amount, $options) = $amount;
        }
        $currency = new Zend_Currency($locale);
        $this->assertSame($amount, $currency->toCurrency(1234.56, $options));

        $date = $this->dateInLocale($locale);
        $this->_testDaysAndMonthTranslations($date, $weekday, $weekdayShort,
            $weekDayNarrow, $monthName, $monthNameShort);

    }

    function dateShortFormatInLocale($locale)
    {
        $date = $this->dateInLocale($locale);
        return $date->get(Zend_Date::DATE_SHORT);
    }

    function dateInLocale($locale)
    {
        return new Zend_Date(gmmktime(0, 0, 0, 4, 5, 2015), null, $locale);
    }

    private function _testDateFormatParsing($otherDate, $locale)
    {
        $date = new Zend_Date($otherDate, null, $locale);
        $shortDate = $date->get(Zend_Date::DATE_SHORT);

        $this->assertEquals($shortDate, $otherDate, 'Format Parsing is Incorrect');
    }

    private function _testDaysAndMonthTranslations($date, $weekday, $weekdayShort,
        $weekDayNarrow, $monthName, $monthNameShort)
    {
        $this->assertEquals($weekday, $date->get(Zend_Date::WEEKDAY));
        $this->assertEquals($weekdayShort,
            $date->get(Zend_Date::WEEKDAY_SHORT));
        $this->assertEquals($weekDayNarrow,
            $date->get(Zend_Date::WEEKDAY_NARROW));
        $this->assertEquals($monthName,
            $date->get(Zend_Date::MONTH_NAME));
        $this->assertEquals($monthNameShort,
            $date->get(Zend_Date::MONTH_NAME_SHORT));
    }
}
