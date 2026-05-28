# 1C Exchange Runtime Notes

Актуальная рабочая схема обмена 1С -> Битрикс для Brakes Systems.

## Источник Правды

- Стандартный импорт Битрикса создает и обновляет товары, цены и остатки.
- Кастомный парсер `local/cron/1c_catalog_parse.php` не импортирует XML напрямую. Он читает уже загруженные товары из БД и поддерживает дерево разделов `категория -> марка -> модель -> кузов`.
- Фото из XML дообрабатываются отдельно: ссылки и файлы из `import___*.xml` синхронизируются в свойства товара и файловое хранилище Битрикса.
- Файлы обмена в `upload/1c_catalog*` нужны для диагностики, но не являются вечным архивом.

## Точки Входа

- `local/php_interface/include/events.php`
  - `catalog:OnSuccessCatalogImport1C` по отдельным XML-файлам.
- `local/php_interface/init.php`
  - обработка `mode=deactivate`;
  - планировщик парсера;
  - обработчик `catalog:OnCompleteCatalogImport1C`;
  - fallback на `main:OnAfterEpilog`.
- `local/cron/1c_catalog_parse.php`
  - CLI/внутренняя функция пересборки разделов и служебных свойств.
- `local/cron/1c_exchange_cleanup.php`
  - безопасная чистка старых папок обмена.

## Как Запускается Обработка

1. `import___*.xml`
   - парсер помечается как ожидающий запуск (`pending=Y`), но сразу не выполняется;
   - запускается обработка фото, если в XML есть ссылки или файлы картинок.

2. `rests___*.xml`
   - фиксируется время и путь последнего файла остатков в опциях `brakes`;
   - парсер здесь не запускается, чтобы не обновлять `TIMESTAMP_X` до штатного `mode=deactivate`.

3. `mode=complete`
   - основной штатный момент запуска парсера;
   - парсер реактивирует используемые разделы, но не включает обратно деактивированные товары.

4. Fallback
   - если после `import___*.xml` парсер остался в `pending`, `OnAfterEpilog` может запустить его позже с источником `OnSuccessTimeoutFallback`.

## Защита `mode=deactivate`

Стандартный `mode=deactivate` может деактивировать разделы, которых нет в полной выгрузке 1С. У нас дерево разделов строится на сайте, поэтому разделы нельзя гасить штатным механизмом.

Текущая логика:

- разделы не деактивируются;
- товары `IBLOCK_ID=1` могут быть деактивированы только если запрос похож на полный обмен;
- проверяется недавний `rests` или `complete`;
- дополнительно проверяется покрытие: если обновлено слишком мало активных товаров, деактивация пропускается;
- результат пишется в `parse.log` и `CEventLog`.

## Чистка Файлов Обмена

Файлы обмена сохраняются для диагностики, но хранятся ограниченно:

- папки `upload/1c_catalog<N>` хранятся 30 дней;
- рабочая папка `upload/1c_catalog` не трогается;
- последние папки из опций `brakes` не трогаются;
- запуск без `--apply` ничего не удаляет.

Команды:

```bash
cd /var/www/www-root/data/www/brakes-systems.ru
php local/cron/1c_exchange_cleanup.php --dry-run --days=30
php local/cron/1c_exchange_cleanup.php --apply --days=30
```

Для cron используем явный срок:

```bash
30 3 * * * php /var/www/www-root/data/www/brakes-systems.ru/local/cron/1c_exchange_cleanup.php --apply --days=30
```

## Логи

Основной лог для диагностики:

- админка Битрикса: `/bitrix/admin/event_log.php`
- `AUDIT_TYPE_ID=BRKS_1C_PARSE`
- `AUDIT_TYPE_ID=BRKS_1C_IMAGE`
- `AUDIT_TYPE_ID=BRKS_1C_DEACT`
- `AUDIT_TYPE_ID=BRKS_1C_EXCHANGE_CLEANUP`

Файловый лог:

- `local/cron/parse.log`

Файловый лог нужен как быстрый технический след. Он не должен быть единственным источником диагностики.

## Быстрая Проверка После Обмена

1. В отчете 1С нет ошибок.
2. В `CEventLog` есть события `BRKS_1C_IMAGE` по `import___*.xml`, если ожидались фото.
3. В `CEventLog` есть `BRKS_1C_PARSE finished` по `OnCompleteCatalogImport1C` или fallback.
4. В `parse.log` есть итоговая строка парсера.
5. На витрине 2-3 контрольных товара имеют цену, остаток, раздел и фото.

## Полезные Команды

Посмотреть кандидатов на чистку без удаления:

```bash
php local/cron/1c_exchange_cleanup.php --dry-run --days=30
```

Проверить последние события парсера/фото:

```bash
php -r '$_SERVER["DOCUMENT_ROOT"]="/var/www/www-root/data/www/brakes-systems.ru";require $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php";$rs=CEventLog::GetList(["ID"=>"DESC"],["AUDIT_TYPE_ID"=>["BRKS_1C_PARSE","BRKS_1C_IMAGE","BRKS_1C_DEACT","BRKS_1C_EXCHANGE_CLEANUP"]],false,["nTopCount"=>20],["ID","TIMESTAMP_X","AUDIT_TYPE_ID","DESCRIPTION"]);while($e=$rs->Fetch()){echo $e["ID"]." ".$e["TIMESTAMP_X"]." ".$e["AUDIT_TYPE_ID"]."\n".$e["DESCRIPTION"]."\n---\n";}'
```
