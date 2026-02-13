# 1C Exchange: Parser + Image Migrator (Brakes Systems)

Этот проект использует стандартный обмен 1С→Битрикс (каталог/цены/остатки) и два пост-обменных шага:

- Каталожный парсер `local/cron/1c_catalog_parse.php`: строит/поддерживает дерево разделов и привязки товаров (категория → марка → модель → кузов) на основе свойств товара в БД.
- Мигратор фото `App\\Brakes\\Helper\\ImageMigrator`: переносит фотографии из ссылок (строковое свойство) в файловое свойство для корректного отображения в UI.

## Что является "источником правды"

- Стандартный импорт 1С создаёт/обновляет элементы в инфоблоке (в нашем кейсе `IBLOCK_ID=1`).
- Парсер НЕ читает XML. Он читает только БД и работает с тем, что уже импортировано стандартным модулем 1С.
- Мигратор фото берёт ссылки из выгрузки 1С и приводит их к виду, удобному для сайта.

## Цепочка запуска

Код точки входа:

- `local/php_interface/include/events.php`: подписка на `catalog:OnSuccessCatalogImport1C` (по файлам импорта).
- `local/php_interface/init.php`: подпись на `catalog:OnCompleteCatalogImport1C` (окончание обмена), планировщик, логирование.

Поведение:

1. На `import___*.xml`:
- Парсер ставится в ожидание (`pending=Y`), но НЕ запускается сразу (`runNow=N`), чтобы не гонять парсер на каждом куске импорта.
- Запускается мигратор фото (если доступны данные "Ссылки на фото").

2. На `rests___*.xml`:
- Парсер запускается сразу (`runNow=Y`) как "безопасная точка" в конце каталожного обмена, если `mode=complete` отсутствует/нестабилен.

3. На `mode=complete`:
- Парсер запускается, если ещё не запускался на этом файле недавно.
- Если парсер уже был запущен на `rests`, запуск на `complete` пропускается как дубль.

## Логи и где смотреть

1. Основной лог (рекомендован): Журнал событий в админке Битрикса.

- `/bitrix/admin/event_log.php`
- `AUDIT_TYPE_ID`:
  - `BRKS_1C_PARSE` (парсер)
  - `BRKS_1C_IMAGE` (миграция фото)
- В описании (`DESCRIPTION`) есть метаданные: `source`, `file`, `runNow`, итоговая статистика `result`.

2. Файловый лог (best-effort):

- `local/cron/parse.log`
- Пишется для диагностики, не должен ломать обмен при проблемах с FS.

## Интерпретация статистики парсера

Ключевые поля:

- `elementsProcessed`: сколько товаров обработано.
- `orphans`: сколько товаров ушли в fallback `other`.
- `skippedLengthMismatch`: сколько товаров пропущено из-за несовпадения количества значений в `MARK/MODEL/BODY`.

Причины fallback в `parse.log`:

- `fallback=other reason=empty_values`: пустые значения `MARK/MODEL/BODY`.
- `fallback=other reason=length_mismatch`: длины массивов `MARK/MODEL/BODY` не совпали (после разбиения по `;`).

## Интерпретация логики мигратора фото

- Стандартный обмен может не передавать бинарные картинки (в отчёте 1С может быть "Выгружено 0 картинок").
- В этом случае фото приходят как реквизит "Ссылки на фото" внутри `import___*.xml` в `<ЗначенияРеквизитов>`.
- Мы синхронизируем эти ссылки в строковое свойство `LINK_PHOTO`, затем `ImageMigrator` переносит их в `LINK_PHOTO_FILE` (файловое свойство).

## Важный нюанс: mode=deactivate

В `local/php_interface/init.php` есть защита, которая перехватывает `mode=deactivate` в `1c_exchange.php` и возвращает `success`.

Причина:

- Стандартный `deactivate` может деактивировать "лишние" разделы, а у нас есть кастомное дерево разделов, которого нет в 1С.

Следствие:

- "Удалённые в 1С" товары не будут автоматически деактивироваться стандартным механизмом `mode=deactivate`.
- Если бизнес-требование такое есть, это делается отдельной логикой (не через общий `deactivate`).

## Производительность (когда станет 1500+ товаров)

Текущая модель запуска (через `shutdown` в web-запросе 1С) может начать упираться в:

- занятость php-fpm воркеров,
- таймауты/память,
- конкуренцию при нескольких файлах обмена подряд.

Рекомендованная эволюция без изменения бизнес-логики:

- Оставить события как "маркер" (`pending=Y` + метаданные).
- Выполнение вынести в CLI-воркер: cron каждые 1–2 минуты запускает `php local/cron/1c_catalog_parse.php` и/или отдельный runner, который проверяет `pending` и выполняет работу.

## Чек-лист прод-проверки после выгрузки (10 пунктов)

1. 1С отчёт по обмену (`upload/1c_catalog/Reports/...utf8.txt`) без `failure/ошибка`.
2. В `upload/1c_catalog/` появились новые `import___*.xml`, `prices___*.xml`, `rests___*.xml`.
3. В `event_log.php` есть `BRKS_1C_IMAGE finished` по `import___*.xml` (где ожидаются ссылки на фото).
4. В `event_log.php` есть `BRKS_1C_PARSE finished` по источнику `OnCompleteCatalogImport1C` или `OnSuccessCatalogImport1C:rests`.
5. Парсер не сработал через fallback (`source=OnSuccessTimeoutFallback`) как основной путь.
6. В `BRKS_1C_PARSE finished` разумные значения `orphans` и `skippedLengthMismatch`.
7. В `BRKS_1C_PARSE finished` `category_updated` и `oem_updated` не "внезапно 0" (если ожидаются обновления).
8. Кол-во товаров в БД (`IBLOCK_ID=1`) соответствует ожиданиям (например, 81 после тестовой выгрузки).
9. `parse.log` содержит строку `src=OnComplete...` или `src=...:rests` с итоговой статистикой.
10. Витрина: 2–3 контрольных товара имеют раздел, цену, остаток/доступность, и отображаемое фото.

## Полезные SSH-команды для быстрой диагностики

Количество элементов в `IBLOCK_ID=1` (без учёта прав):

```bash
php -r '$_SERVER["DOCUMENT_ROOT"]="/var/www/www-root/data/www/brakes-systems.ru";require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";\\Bitrix\\Main\\Loader::includeModule("iblock");echo "iblock1_count_no_perm=".\\CIBlockElement::GetList([],["IBLOCK_ID"=>1,"CHECK_PERMISSIONS"=>"N"],[]).PHP_EOL;'
```

Последние события по парсеру/мигратору:

```bash
php -r '$_SERVER["DOCUMENT_ROOT"]="/var/www/www-root/data/www/brakes-systems.ru";require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";$rs=CEventLog::GetList(["ID"=>"DESC"],["AUDIT_TYPE_ID"=>["BRKS_1C_PARSE","BRKS_1C_IMAGE"]],false,["nTopCount"=>20],["ID","TIMESTAMP_X","AUDIT_TYPE_ID","REQUEST_URI","DESCRIPTION"]);while($e=$rs->Fetch()){echo $e["ID"]." ".$e["TIMESTAMP_X"]." ".$e["AUDIT_TYPE_ID"]." ".$e["REQUEST_URI"].\"\\n\".$e[\"DESCRIPTION\"].\"\\n---\\n\";}' 
```

