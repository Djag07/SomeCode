<?php

/*
Here is implemented the display of three collections of geo objects on the Yandex map with balloons and switches between collections
*/

const API_KEY = '1a22aa33-aa44-5a6a-77a8-a9999999999a';

use Bitrix\Main\Page\Asset;
Asset::getInstance()->addJs("https://api-maps.yandex.ru/2.1/?lang=ru_RU&amp;apikey=" . API_KEY);
?>

<script>
	var first_points = <?=$arResult['JS_FIRST_POINTS']?>,
		second_points = <?=$arResult['JS_SECOND_POINTS']?>,
		third_points = <?=$arResult['JS_THIRD_POINTS']?>;

	ymaps.ready(function () {

		var myMap = new ymaps.Map('map-container', {
				center: [59.938955, 30.315644],
				zoom: 13,
				controls: ['smallMapDefaultSet'],
				touchScroll: true
			}),

			First_points = new ymaps.GeoObjectCollection({}, {
				preset: "",
				strokeWidth: 4,
				geodesic: true
			}),

			Second_points = new ymaps.GeoObjectCollection({}, {
				preset: "",
				strokeWidth: 4,
				geodesic: true
			}),

			Third_points = new ymaps.GeoObjectCollection({}, {
				preset: "",
				strokeWidth: 4,
				geodesic: true
			});

		function AddFirstPoints(image) {
			$.each(first_points, function(index, value) {

				let picture = (this.PICTURE) ? "<div class='balImg col-6'><img src='" + this.PICTURE + "'></div><div class='balDescr col-6'>" : 
						"<div class='balDescr col-12'>",
					name = (this.NAME) ? "<div class='balHeader'>" + this.NAME + "</div>" : '',
					address = (this.ADDRESS) ? "<div class='balAddr'><b>Адрес:</b><p>" + this.ADDRESS + "</p></div>" : '',
					interval = (this.FIRST_POINTS_TIMEWORK) ? "<div class='balInterval'><b>Период работы:</b><p>" + this.FIRST_POINTS_TIMEWORK + "</p></div>" : '',
					start = (this.FIRST_POINTS_WORK_START_DATE) ? "<div class='balStart'><b>Дата открытия:</b><p>" + this.FIRST_POINTS_WORK_START_DATE + "</p></div>" : '',
					end = (this.FIRST_POINTS_WORK_END_DATE) ? "<div class='balEnd'><b>Дата закрытия:</b><p>" + this.FIRST_POINTS_WORK_END_DATE + "</p></div>" : '';

				index = new ymaps.Placemark([this.LATITUDE, this.LONGITUDE], {
					balloonContentHeader: '',
					// Зададим содержимое основной части балуна.
					balloonContentBody: "<div class='balContent row d-flex'>" + picture + name + address + interval + start + end + "</div></div>",
					// Зададим содержимое нижней части балуна.
					balloonContentFooter: '',
					// Зададим содержимое всплывающей подсказки.
					hintContent: this.NAME,
					custom: image
				}, {
					iconLayout: 'default#image',
					iconImageHref: image + '.svg',
					iconImageSize: [32, 52],
					zIndex:1000,
					balloonShadow: true,
					balloonPanelMaxHeightRatio: 1,
					balloonPanelMaxMapArea: '0',
					balloonMaxWidth: 500,
					balloonMaxHeight: 600
				});
	
				First_points.add(index);
			});
			myMap.geoObjects.add(First_points);
		}

		function AddSecondPoints(image) {
			$.each(second_points, function(index, value) {

				let picture = (this.PICTURE) ? "<div class='balImg col-6'><img src='" + this.PICTURE + "'></div><div class='balDescr col-6'>" : 
						"<div class='balDescr col-12'>",
					name = (this.NAME) ? "<div class='balHeader'>" + this.NAME + "</div>" : '',
					type = (this.SECOND_POINTS_TYPE) ? "<div class='balType'><b>Тип:</b><p>" + this.SECOND_POINTS_TYPE + "</p></div>" : '',
					district = (this.SECOND_POINTS_DISTRICT) ? "<div class='balDistrict'><b>Район:</b><p>" + this.SECOND_POINTS_DISTRICT + "</p></div>" : '',
					timework = (this.SECOND_POINTS_TIMEWORK) ? "<div class='balPeriod'><b>Период работы:</b><p>" + this.SECOND_POINTS_TIMEWORK + "</p></div>" : '';

				index = new ymaps.Placemark([this.LATITUDE, this.LONGITUDE], {
					balloonContentHeader: '',
					// Зададим содержимое основной части балуна.
					balloonContentBody: "<div class='balContent row d-flex'>" + picture + name + type + district + timework + "</div></div>",
					// Зададим содержимое нижней части балуна.
					balloonContentFooter: '',
					// Зададим содержимое всплывающей подсказки.
					hintContent: this.NAME,
					custom: image
				}, {
					iconLayout: 'default#image',
					iconImageHref: image + '.svg',
					iconImageSize: [32, 52],
					zIndex:1000,
					balloonShadow: true,
					balloonPanelMaxHeightRatio: 1,
					balloonPanelMaxMapArea: '0',
					balloonMaxWidth: 500,
					balloonMaxHeight: 600
				});
	
				Second_points.add(index);
			});
			myMap.geoObjects.add(Second_points);
		}

		function AddThirdPoints(image) {
			$.each(third_points, function(index, value) {

				let picture = (this.PICTURE) ? "<div class='balImg col-6'><img src='" + this.PICTURE + "'></div><div class='balDescr col-6'>" : 
						"<div class='balDescr col-12'>",
					name = (this.NAME) ? "<div class='balHeader'>" + this.NAME + "</div>" : '',
					type = (this.THIRD_POINTS_OBJECT) ? "<div class='balType'><b>Вид работ:</b><p>" + this.THIRD_POINTS_OBJECT + "</p></div>" : '',
					interval = (this.THIRD_POINTS_START_DATE && this.THIRD_POINTS_END_DATE) ? "<div class='balInterval'><b>Сроки ремонта:</b><p>" + 
            this.THIRD_POINTS_START_DATE + " - " + this.THIRD_POINTS_END_DATE + "</p></div>" : '',
					address = (this.THIRD_POINTS_ADDRESSES) ? "<div class='balAddr'><b>Адрес:</b><p>" + this.THIRD_POINTS_ADDRESSES + "</p></div>" : '',
					constrains = (this.THIRD_POINTS_CONSTRAINS) ? "<div class='balStop'><b>Ограничения ремонта:</b><p>" + this.THIRD_POINTS_CONSTRAINS + 
            "</p></div>" : '',
					close = (this.THIRD_POINTS_ROAD_CLOSE) ? "<div class='balStop'><b>Ограничение движения:</b><p>" + this.THIRD_POINTS_ROAD_CLOSE + "</p></div>" : '',
					comment = (this.THIRD_POINTS_COMMENT) ? "<div class='balStop'><b>Альтернативное снабжение:</b><p>" + this.THIRD_POINTS_COMMENT + "</p></div>" : '';

				index = new ymaps.Placemark([this.LATITUDE, this.LONGITUDE], {
					balloonContentHeader: '',
					// Зададим содержимое основной части балуна.
					balloonContentBody: "<div class='balContent row d-flex'>"+ picture + name + type + interval + address + constrains + close + comment +"</div></div>",
					// Зададим содержимое нижней части балуна.
					balloonContentFooter: '',
					// Зададим содержимое всплывающей подсказки.
					hintContent: this.NAME,
					custom: image
				}, {
					iconLayout: 'default#image',
					iconImageHref: image + '.svg',
					iconImageSize: [32, 52],
					zIndex:1000,
					balloonShadow: true,
					balloonPanelMaxHeightRatio: 1,
					balloonPanelMaxMapArea: '0',
					balloonMaxWidth: 500,
					balloonMaxHeight: 600
				});
	
				Third_points.add(index);
			});
			myMap.geoObjects.add(Third_points);
		}

		<?if ($arParams['TYPE_OF_POINTS'] && ($arParams['TYPE_OF_POINTS'] == '2')):?>
			AddFirstPoints('first_points_act');
		<?elseif ($arParams['TYPE_OF_POINTS'] && ($arParams['TYPE_OF_POINTS'] == '3')):?>
			AddSecondPoints('second_points_act');
		<?endif;?>

		function initExtSearch() {
			var suggestView = new ymaps.SuggestView('suggest'),
				searchControl = myMap.controls.get('searchControl');

			$( ".extSearch svg" ).click(function() {
				searchControl.search($('#suggest').val());
			});
			$('#suggest').keydown(function(e) {
				if(e.keyCode === 13) {
					searchControl.search($(this).val());
				}
			});
		}

		$("#map-second-points:checkbox").change(function(){
			if($(this).prop('checked')){
				if (!First_points.getLength() || First_points.get(1).properties._data.custom.indexOf("act") == -1) {
					First_points.removeAll();
				}
				Second_points.removeAll();
				if (!Third_points.getLength() || Third_points.get(1).properties._data.custom.indexOf("act") == -1) {
					Third_points.removeAll();
				}
				AddSecondPoints('second_points_act');
			}else{
				Second_points.removeAll();
			}
		});

		$("#map-first-points:checkbox").change(function(){
			if($(this).prop('checked')){
				First_points.removeAll();
				if (!Second_points.getLength() || Second_points.get(1).properties._data.custom.indexOf("act") == -1) {
					Second_points.removeAll();
				}
				if (!Third_points.getLength() || Third_points.get(1).properties._data.custom.indexOf("act") == -1) {
					Third_points.removeAll();
				}
				AddFirstPoints('fountain_act');
			}else{
				First_points.removeAll();
			}
		}); 

		$("#map-third-points:checkbox").change(function(){
			var searchControl = myMap.controls.get('searchControl');

			if($(this).prop('checked')){
				$('.extSearch').show();
				initExtSearch();
				if (!First_points.getLength() || First_points.get(1).properties._data.custom.indexOf("act") == -1) {
					First_points.removeAll();
				}
				if (!Second_points.getLength() || Second_points.get(1).properties._data.custom.indexOf("act") == -1) {
					Second_points.removeAll();
				}
				Third_points.removeAll();
				AddThird_points('third_points_act');
			}else{
				$('.extSearch').hide();
				searchControl.clear();
				$('#suggest').val('');
				Third_points.removeAll();
				if ((Second_points.getLength() && Second_points.get(1).properties._data.custom.indexOf("act") != -1) ||
					(First_points.getLength() && First_points.get(1).properties._data.custom.indexOf("act") != -1)) {
				} else {
					myMap.setCenter([59.938955, 30.315644], 13);
				}
			}
		}); 
	});
</script>
