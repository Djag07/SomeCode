<?php
/**
 * This script used for obtaining clients info for desirable period
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

use Bitrix\Main\Localization\Loc;

$APPLICATION->SetTitle(Loc::getMessage('HNDL_CPE_HEADER'));

CJSCore::Init(array('jquery'));

$currentDate = new DateTime('now');
$currentDate = $currentDate->format('d.m.Y');

if (isset($_GET) && count($_GET)) {
    // Form processing
    require_once 'clientsPeriodExport_handler.php';
}
?>

    <style>
        form#clientsPeriodExport .formLabel {
            width: 180px;
            display: inline-block;
            margin-bottom: 10px;
            vertical-align: middle;
        }

        form#clientsPeriodExport input[type='text'] {
            width: 147px;
            margin: 0 0 14px !important;
        }

        p.success, p.error {
            font-size: 16px;
            font-weight: bold;
        }

        p.success {
            color: green;
        }

        p.error {
            color: red;
        }

        p.info, li {
            font-size: 15px;
            color: #454545;
        }

        #loadingImage {
            vertical-align: top;
            margin: 30px;
            display: none;
        }
    </style>

    <script>
        var csvArray = [], // CSV data array
            addCSVHeaders = true;   // Add headers to csv file?

        /**
         * Method returns current time
         *
         * @return string
         */
        Date.prototype.timeNow = function () {
            return ((this.getHours() < 10) ? "0" : "") + this.getHours() + ":" + ((this.getMinutes() < 10) ? "0" : "") +
                this.getMinutes() + ":" + ((this.getSeconds() < 10) ? "0" : "") + this.getSeconds();
        }

        /**
         * Method adds days count to JS Date object
         *
         * @param days
         *
         * @return {Date}
         */
        Date.prototype.addDays = function (days) {
            var date = new Date(this.valueOf());
            date.setDate(date.getDate() + days);
            return date;
        }


        /**
         * Method returns array of start date and end date as JS Date objects
         *
         * @return array ['startDate', 'endDate']
         */
        function returnDatesAsDateObject() {
            var arStartDate = $('#startDate').val().split('.'),
                arEndDate = $('#endDate').val().split('.'),
                startDate = new Date(arStartDate[2], (arStartDate[1] - 1), arStartDate[0]),
                endDate = new Date(arEndDate[2], (arEndDate[1] - 1), arEndDate[0]);

            return [startDate, endDate];
        }

        /**
         * Method returns formatted date for different purposes
         *
         * @param {object} date Date object
         * @param {boolean} phpStyle return date for php script
         *
         * @return string
         */
        function formatDate(date, phpStyle = true) {
            var day = date.getDate(),
                month = date.getMonth() + 1,
                year = date.getFullYear();

            if (phpStyle) {
                return year + '-' + month + '-' + day;
            } else {
                return ((day > 9) ? day : '0' + day) + '_' + ((month > 9) ? month : '0' + month) + '_' +
                    date.getFullYear();
            }
        }

        /**
         * Method compares start and end date
         *
         * @return {boolean}
         */
        function checkDates() {
            [startDate, endDate] = returnDatesAsDateObject();
            return (startDate.getTime() <= endDate.getTime());
        }

        /**
         * Recursive method to get info for 1 week interval
         *
         * @param {object} currentStartDate Start date (JS Date object)
         * @param {object} currentEndDate End date (JS Date object)
         *
         * @return {bool}
         */
        function performAjax(currentStartDate, currentEndDate, onlyPayed) {
            $.ajax({
                type: 'POST',
                url: '/local/lib/scripts/php/handlers/clientsPeriodExport_handler.php',
                data: 'is_ajax=y&addCSVHeaders=' + addCSVHeaders + '&startDate=' + formatDate(currentStartDate) +
                    '&endDate=' + formatDate(currentEndDate) + '&onlyPayed=' + onlyPayed,
                dataType: 'json',
                success: function (resultArray) {
					$('#workingResult').append(resultArray['description'] + "\n");
					if (!!resultArray['data']) {
						let result = JSON.parse(resultArray['data']);
						csvArray = [].concat(csvArray, result);
					}

                    currentStartDate = currentStartDate.addDays(7);
                    currentEndDate = currentStartDate.addDays(6);
                    if (currentStartDate > endDate) {
                        $('#workingResult').append('<?= Loc::getMessage('HNDL_CPE_LINK_PREPARING'); ?>' + "\n");
                        // End of recursion
                        sendFileToUser(JSON.stringify(csvArray));
                        return true;
                    }
                    if (currentEndDate > endDate) {
                        currentEndDate = endDate;
                    }

                    addCSVHeaders = false;
                    performAjax(currentStartDate, currentEndDate, onlyPayed);
                }
            });
        }

		    var clientsTypeDaysToAdd = 4;

        /**
         * Method perform last request to generate file and link on it
         *
         * @return {boolean}
         */
        function sendFileToUser(csvArray) {
            [startDate, endDate] = returnDatesAsDateObject();

            $.ajax({
                type: 'POST',
                url: '/local/lib/scripts/php/handlers/clientsPeriodExport_handler.php',
				        data: {"is_ajax":"y", "send_file":"true", "data": csvArray},
                dataType: 'text',
                success: function () {
                    var now = new Date();
                    $('#workingResult').append('<?= Loc::getMessage('HNDL_CPE_EXPORT_END'); ?>'  + now.timeNow() + " ---\n");
                    $('#linkContainer').html('<a download="clients_' + formatDate(startDate, false) + '-' + formatDate(endDate, false) +
                        '.csv" href="/local/lib/scripts/php/handlers/clients.csv">' +
                        '<?= Loc::getMessage('HNDL_CPE_DOWNLOAD_STR_1'); ?></a> ' +
                        '<?= Loc::getMessage('HNDL_CPE_DOWNLOAD_STR_2'); ?>');
                }
            });

            return true;
        }

        $(document).ready(function () {
            $(document).on('click', 'input#getExport', function (e) {
                $('#linkContainer').html('');
                $('#workingResult').text('');
                addCSVHeaders = true;

                if (!checkDates()) {
                    alert('<?= Loc::getMessage('HNDL_CPE_DATES_ERROR'); ?>');
                    e.preventDefault();
                    return false;
                }

                [startDate, endDate] = returnDatesAsDateObject();

                var currentStartDate = startDate,
                    currentEndDate = startDate.addDays(6),
                    now = new Date(),
                    onlyPayed = ('undefined' !== typeof $('#onlyPayed').attr('checked'));

                if (currentEndDate > endDate) {
                    currentEndDate = endDate;
                }

                if (!onlyPayed) {
                    $('#workingResult').append('<?= Loc::getMessage('HNDL_CPE_EXPORT_START'); ?>' + now.timeNow() + " ---\n");
                    performAjax(currentStartDate, currentEndDate, false);
                } else {
                    currentEndDate = startDate.addDays(clientsTypeDaysToAdd);
                    performAjax(currentStartDate, currentEndDate, onlyPayed);
                }
            });

            $('#loadingImage').bind('ajaxStart', function () {
                $(this).show();
            }).bind('ajaxStop', function () {
                $(this).hide();
            });
        });
    </script>

    <form action="" method="get" name="clientsPeriodExport" id="clientsPeriodExport">
        <label for="startDate" class="formLabel"><?= Loc::getMessage('HNDL_CPE_START_DATE'); ?></label>
        <input type="text" value="<?= $currentDate; ?>" name="startDate" id="startDate"
               onclick="BX.calendar({node: this, field: this, bTime: false});">
        <br/>

        <label for="endDate" class="formLabel"><?= Loc::getMessage('HNDL_CPE_END_DATE'); ?></label>
        <input type="text" value="<?= $currentDate; ?>" name="endDate" id="endDate"
               onclick="BX.calendar({node: this, field: this, bTime: false});">
        <br/>

        <label for="onlyPayed" class="formLabel"><?= Loc::getMessage('HNDL_CPE_ONLY_PAYED'); ?></label>
        <input type="checkbox" name="onlyPayed" id="onlyPayed" />
        <br/><br/>

        <label for="workingResult"><?= Loc::getMessage('HNDL_CPE_RESULTS'); ?></label><br/>
        <textarea cols="50" rows="10" id="workingResult"></textarea>
        <img src="/bitrix/js/im/images/loading.gif" id="loadingImage" alt="loadingImage"/>
        <br/><br/>

        <div id="linkContainer"></div>
        <br/>

        <input class="adm-btn adm-btn-save inputField" type="button"
               value="<?= Loc::getMessage('HNDL_CPE_LOAD_BUTTON'); ?>"
               id="getExport" name="getExport"/>
    </form>

<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
