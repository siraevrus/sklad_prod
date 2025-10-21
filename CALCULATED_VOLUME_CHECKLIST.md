# ✅ Чек-лист calculated_volume

## 🎯 Для понимания механизма

- [ ] Прочитал CALCULATED_VOLUME_README.md (5 мин)
- [ ] Прочитал CALCULATED_VOLUME_SUMMARY.md (15 мин)
- [ ] Посмотрел диаграмму в CALCULATED_VOLUME_DIAGRAM.md (10 мин)
- [ ] Понимаю, что calculated_volume это объем за 1 единицу
- [ ] Понимаю, как работает testFormula()
- [ ] Знаю, где хранится calculated_volume (таблица products)

## 🔧 Для разработчика

- [ ] Изучил Product::calculateVolume() в Product.php:336
- [ ] Изучил Product::updateCalculatedVolume() в Product.php:385
- [ ] Изучил ProductTemplate::testFormula() в ProductTemplate.php:68
- [ ] Понимаю afterStateUpdated() в ProductResource.php:123
- [ ] Понимаю mutateFormDataBeforeCreate() в CreateProduct.php:59
- [ ] Понимаю mutateFormDataBeforeSave() в EditProduct.php:21

## 🐛 Для отладки

- [ ] Знаю, что calculated_volume = null означает незаполненные атрибуты
- [ ] Знаю максимальное значение: 999999999.9999
- [ ] Знаю, что это disabled поле и не может быть изменено вручную
- [ ] Знаю, как пересчитать старый товар (редактировать и сохранить)
- [ ] Знаю, что при изменении формулы старые товары не пересчитываются

## 🧮 Формула расчета

```
ВХОД:
  Шаблон формула: "length * width * height / 1000000"
  Атрибуты: {length: 2000, width: 100, height: 25}

ПРОЦЕСС:
  1. Подставить: "2000 * 100 * 25 / 1000000"
  2. Вычислить: 5000000 / 1000000 = 5
  3. Вернуть: {success: true, result: 5}

ВЫХОД:
  calculated_volume = 5.0000 м³ (за 1 единицу)
```

## 📊 Примеры

### Пример 1: Доска
```
Шаблон:        "Доска" (ID: 1)
Формула:       "length * width * height / 1000000"
Характеристики:
  length:      2000 мм
  width:       100 мм
  height:      25 мм
Результат:     5.0000 м³ (за 1 шт)
При qty=100:   500.0000 м³ (всего)
```

### Пример 2: Листовой материал
```
Шаблон:        "Лист" (ID: 2)
Формула:       "length * width * thickness / 1000000"
Характеристики:
  length:      1000 мм
  width:       500 мм
  thickness:   10 мм
Результат:     5.0000 м³ (за 1 лист)
```

## 🔄 Процесс создания товара

```
1. Открыть форму создания
   ↓
2. Выбрать шаблон товара
   ↓
3. Заполнить характеристики
   ↓
4. Система вычисляет calculated_volume (live)
   ↓
5. Проверить результат в "Рассчитанный объем"
   ↓
6. Нажать СОХРАНИТЬ
   ↓
7. CreateProduct::mutateFormDataBeforeCreate() вызывает финальный расчет
   ↓
8. ✓ Товар создан с calculated_volume
```

## 📁 Важные файлы

| Файл | Метод | Описание |
|------|-------|---------|
| Product.php | calculateVolume() | Вычисляет объем по формуле |
| Product.php | updateCalculatedVolume() | Пересчитывает и сохраняет |
| Product.php | getTotalVolume() | Общий объем (cv * qty) |
| ProductTemplate.php | testFormula() | Главный метод расчета |
| ProductResource.php | form() | Форма с live обновлением |
| CreateProduct.php | mutateFormDataBeforeCreate() | Финальный расчет при создании |
| EditProduct.php | mutateFormDataBeforeSave() | Финальный расчет при редактировании |

## ❓ Частые вопросы

**Q: Где я вижу calculated_volume?**
- A: В форме создания/редактирования товара как "Рассчитанный объем"

**Q: Почему calculated_volume = null?**
- A: Не заполнены все необходимые характеристики

**Q: Может ли я вручную изменить calculated_volume?**
- A: Нет, это disabled поле

**Q: Как пересчитать calculated_volume?**
- A: Откройте товар, измените любое поле и сохраните

**Q: Что если я изменю формулу?**
- A: Новые товары будут использовать новую формулу, старые нужно пересчитать вручную

**Q: Как получить общий объем?**
- A: total_volume = calculated_volume * quantity

## 🎓 Ключевые концепции

- **calculated_volume** - объем за ОДНУ единицу товара
- **total_volume** - общий объем (calculated_volume * quantity)
- **attributes** - характеристики товара в JSON (length, width, height и т.д.)
- **formula** - математическое выражение в шаблоне
- **testFormula()** - метод, который подставляет и вычисляет формулу
- **live обновление** - автоматический расчет при изменении поля

## ✅ Что я теперь знаю

- [ ] Что такое calculated_volume и как он рассчитывается
- [ ] Все этапы заполнения (выбор → ввод → расчет → сохранение)
- [ ] Какие методы используются (testFormula, calculateVolume, updateCalculatedVolume)
- [ ] Как это работает в Filament форме (live обновления, afterStateUpdated)
- [ ] Где хранится (таблица products, колонка calculated_volume)
- [ ] Как это используется (в расчетах объемов, в API)
- [ ] Как отладить проблемы

---

**Дата:** October 21, 2025  
**Версия:** 1.0  
**Статус:** ✅ Готово

