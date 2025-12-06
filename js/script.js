// Общие утилиты: год
document.addEventListener('DOMContentLoaded', function(){
    // Год в footer на главной
    var y = new Date().getFullYear();
    var el = document.getElementById('year');
    if(el) el.textContent = y;

    // Добавление в корзину — демо (product.html)
    var addToCart = document.getElementById('addToCart');
    if(addToCart){
        addToCart.addEventListener('click', function(){
            alert('Товар добавлен в корзину (демо).');
        });
    }

    // Инициализация функционала профиля
    initProfileFunctionality();

    // Инициализация функционала карты
    initMapFunctionality();

    // Инициализация функционала корзины
    initCartFunctionality();

    // Инициализация функционала оформления заказа
    initCheckoutFunctionality();

    // Инициализация функционала формы контактов
    initContactFormFunctionality();

    // Инициализация бургер-меню
    initBurgerMenu();

    // Инициализация универсальных счетчиков символов
    initCharCounters();
});

// Функционал бургер-меню
function initBurgerMenu() {
    const burgerMenuBtn = document.getElementById('burgerMenuBtn');
    const burgerCloseBtn = document.getElementById('burgerCloseBtn');
    const burgerMenu = document.getElementById('burgerMenu');
    const burgerOverlay = document.getElementById('burgerMenuOverlay');

    if (!burgerMenuBtn || !burgerMenu) return;

    // Открытие бургер-меню
    burgerMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        burgerMenu.classList.add('active');
        burgerOverlay.classList.add('active');
        burgerMenuBtn.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    // Закрытие бургер-меню
    function closeBurgerMenu() {
        burgerMenu.classList.remove('active');
        burgerOverlay.classList.remove('active');
        burgerMenuBtn.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (burgerCloseBtn) {
        burgerCloseBtn.addEventListener('click', closeBurgerMenu);
    }

    if (burgerOverlay) {
        burgerOverlay.addEventListener('click', closeBurgerMenu);
    }

    // Закрытие при клике на ссылку внутри меню
    const burgerLinks = burgerMenu.querySelectorAll('.burger-link');
    burgerLinks.forEach(link => {
        link.addEventListener('click', function() {
            closeBurgerMenu();
        });
    });

    // Закрытие при нажатии Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && burgerMenu.classList.contains('active')) {
            closeBurgerMenu();
        }
    });

    // Предотвращаем закрытие при клике внутри меню
    burgerMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
}

// Функционал для увеличения карты
function initMapFunctionality() {
    const expandBtn = document.querySelector('.map-expand-btn');
    const modalMap = document.getElementById('mapModal');
    const closeMapModal = document.querySelector('.close-map-modal');

    if (expandBtn && modalMap) {
        expandBtn.addEventListener('click', function() {
            modalMap.style.display = 'flex';
        });
    }

    if (closeMapModal && modalMap) {
        closeMapModal.addEventListener('click', function() {
            modalMap.style.display = 'none';
        });
    }

    // Закрытие модального окна при клике вне карты
    if (modalMap) {
        window.addEventListener('click', function(event) {
            if (event.target === modalMap) {
                modalMap.style.display = 'none';
            }
        });
    }
}

// Функционал для страницы профиля
function initProfileFunctionality() {
    // Проверяем, находимся ли мы на странице профиля
    const editProfileBtn = document.getElementById('editProfile');
    if (!editProfileBtn) return;

    // Элементы DOM
    const avatarInput = document.getElementById('avatarInput');
    const userAvatar = document.getElementById('userAvatar');
    const editModal = document.getElementById('editModal');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelEditBtn = document.getElementById('cancelEdit');

    // Загрузка новой аватарки (локальная функция, не сохраняется в БД)
    if (avatarInput && userAvatar) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Проверка типа файла
                if (!file.type.startsWith('image/')) {
                    alert('Пожалуйста, выберите файл изображения');
                    return;
                }

                // Проверка размера файла (макс. 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Размер файла не должен превышать 5MB');
                    return;
                }

                const reader = new FileReader();

                reader.onload = function(e) {
                    userAvatar.src = e.target.result;
                    showNotification('Аватар успешно обновлен! (локально)');
                };

                reader.onerror = function() {
                    alert('Ошибка при чтении файла');
                };

                reader.readAsDataURL(file);
            }
        });
    }

    // Открытие модального окна редактирования
    editProfileBtn.addEventListener('click', function() {
        // Показываем модальное окно
        editModal.style.display = 'flex';
    });

    // Закрытие модального окна
    function closeModal() {
        editModal.style.display = 'none';
    }

    // Обработчики закрытия модального окна
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeModal);
    }

    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', closeModal);
    }

    // Клик вне модального окна для закрытия
    editModal.addEventListener('click', function(e) {
        if (e.target === editModal) {
            closeModal();
        }
    });

    // Показать уведомление (только для локальных изменений)
    function showNotification(message) {
        // Удаляем существующие уведомления
        const existingNotifications = document.querySelectorAll('.custom-notification');
        existingNotifications.forEach(notification => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        });

        const notification = document.createElement('div');
        notification.className = 'custom-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--accent);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1001;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (notification.parentNode) {
                    document.body.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Добавляем стили для анимации уведомлений
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
}

// Функционал корзины
function initCartFunctionality() {
    const addToCartForms = document.querySelectorAll('form[action*="add_to_cart"]');

    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const quantity = this.querySelector('input[name="quantity"]').value;
            // Можно добавить дополнительную валидацию здесь
        });
    });

    // Добавляем подтверждение при удалении товара
    const removeLinks = document.querySelectorAll('.remove-btn');
    removeLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Вы уверены, что хотите удалить этот товар из корзины?')) {
                e.preventDefault();
            }
        });
    });

    // Добавляем валидацию для полей количества
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 1) {
                this.value = 1;
            }
            if (this.value > 99) {
                this.value = 99;
            }
        });
    });
}

// Функционал для оформления заказа
function initCheckoutFunctionality() {
    const checkoutForm = document.getElementById('checkoutForm');
    if (!checkoutForm) return;

    // Получаем данные из data-атрибутов или используем значения по умолчанию
    const totalProductsElement = document.querySelector('[data-total-products]');
    const totalProducts = totalProductsElement ? parseFloat(totalProductsElement.dataset.totalProducts) : 0;

    let deliveryCost = 0;

    const maxBonusElement = document.querySelector('[data-max-bonus]');
    const maxBonusUse = maxBonusElement ? parseFloat(maxBonusElement.dataset.maxBonus) : 0;

    const bonusProgramElement = document.querySelector('[data-bonus-program]');
    const isBonusProgram = bonusProgramElement ? bonusProgramElement.dataset.bonusProgram === 'true' : false;

    const useBonusInput = document.getElementById('use_bonus');
    const deliveryMethodSelect = document.getElementById('delivery_method');
    const bonusUseValue = document.getElementById('bonus_use_value');
    const orderTotal = document.getElementById('order_total');
    const deliveryCostLine = document.getElementById('delivery_cost_line');
    const bonusAccrual = document.getElementById('bonus_accrual');

    function updateOrderSummary() {
        let totalAmount = totalProducts + deliveryCost;
        let usedBonus = 0;

        if (isBonusProgram && useBonusInput) {
            usedBonus = parseFloat(useBonusInput.value) || 0;
            if (usedBonus > maxBonusUse) {
                usedBonus = maxBonusUse;
                useBonusInput.value = maxBonusUse;
            }
            totalAmount -= usedBonus;
            if (totalAmount < 0) totalAmount = 0;

            if (bonusUseValue) {
                bonusUseValue.textContent = usedBonus.toFixed(2);
            }
        }

        if (orderTotal) {
            orderTotal.textContent = 'Итого: ₽' + totalAmount.toFixed(2);
        }

        if (bonusAccrual) {
            const newBonusAccrual = totalAmount * 0.05;
            bonusAccrual.textContent = newBonusAccrual.toFixed(2);
        }
    }

    if (deliveryMethodSelect) {
        deliveryMethodSelect.addEventListener('change', function() {
            switch(this.value) {
                case 'courier':
                    deliveryCost = 300;
                    break;
                case 'post':
                    deliveryCost = 200;
                    break;
                case 'pickup':
                    deliveryCost = 0;
                    break;
                default:
                    deliveryCost = 0;
            }

            if (deliveryCostLine) {
                deliveryCostLine.innerHTML = '<span>Доставка:</span><span>₽' + deliveryCost + '</span>';
            }

            updateOrderSummary();
        });
    }

    if (useBonusInput) {
        useBonusInput.addEventListener('input', updateOrderSummary);
        useBonusInput.addEventListener('change', updateOrderSummary);
    }

    const paymentMethods = document.querySelectorAll('.payment-method');
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            paymentMethods.forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });

    function setupCharCounter(inputId, counterId, maxLength) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);

        function updateCounter() {
            const length = input.value.length;
            counter.textContent = `${length}/${maxLength}`;

            if (length > maxLength * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }

            if (length > maxLength) {
                input.value = input.value.substring(0, maxLength);
                updateCounter();
            }
        }

        input.addEventListener('input', updateCounter);
        input.addEventListener('change', updateCounter);
        updateCounter();
    }

    // Настройка счетчиков символов
    setupCharCounter('full_name', 'full_name_counter', 100);
    setupCharCounter('phone', 'phone_counter', 20);
    setupCharCounter('email', 'email_counter', 255);
    setupCharCounter('address', 'address_counter', 500);
    setupCharCounter('city', 'city_counter', 50);
    setupCharCounter('postal_code', 'postal_code_counter', 10);
    setupCharCounter('comments', 'comments_counter', 1000);

    // Валидация формы
    checkoutForm.addEventListener('submit', function(e) {
        let valid = true;

        const requiredFields = this.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                valid = false;
                field.style.borderColor = '#dc3545';
            } else {
                field.style.borderColor = '#e6e6e6';
            }
        });

        const textFields = this.querySelectorAll('input, textarea');
        textFields.forEach(field => {
            const maxLength = parseInt(field.getAttribute('maxlength'));
            if (maxLength && field.value.length > maxLength) {
                valid = false;
                field.style.borderColor = '#dc3545';
                const counter = document.getElementById(field.id + '_counter');
                if (counter) counter.classList.add('warning');
            }
        });

        if (useBonusInput) {
            const usedBonus = parseFloat(useBonusInput.value) || 0;
            if (usedBonus > maxBonusUse) {
                valid = false;
                useBonusInput.style.borderColor = '#dc3545';
                alert('Нельзя использовать больше ' + maxBonusUse + ' бонусов');
            }
        }

        if (!valid) {
            e.preventDefault();
            alert('Пожалуйста, проверьте заполнение всех полей.');
        }
    });
}

// Функционал для формы контактов
function initContactFormFunctionality() {
    const contactForm = document.getElementById('contactForm');
    if (!contactForm) return;

    // Функция для счетчика символов
    function setupCharCounter(inputId, counterId, maxLength) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);

        if (!input || !counter) return;

        function updateCounter() {
            const length = input.value.length;
            counter.textContent = `${length}/${maxLength}`;

            if (length > maxLength * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }

            // Автоматическое обрезание при превышении лимита
            if (length > maxLength) {
                input.value = input.value.substring(0, maxLength);
                updateCounter();
            }
        }

        input.addEventListener('input', updateCounter);
        input.addEventListener('change', updateCounter);
        updateCounter(); // Инициализация при загрузке
    }

    // Настройка счетчиков для всех полей
    setupCharCounter('name', 'name_counter', 100);
    setupCharCounter('email', 'email_counter', 255);
    setupCharCounter('phone', 'phone_counter', 20);
    setupCharCounter('message', 'message_counter', 1000);

    // Валидация формы перед отправкой
    contactForm.addEventListener('submit', function(e) {
        let valid = true;
        const fields = this.querySelectorAll('input, textarea');

        fields.forEach(field => {
            const maxLength = parseInt(field.getAttribute('maxlength'));
            if (maxLength && field.value.length > maxLength) {
                valid = false;
                field.style.borderColor = '#dc3545';
                const counter = document.getElementById(field.name + '_counter');
                if (counter) counter.classList.add('warning');
            } else {
                field.style.borderColor = '';
            }

            if (field.hasAttribute('required') && !field.value.trim()) {
                valid = false;
                field.style.borderColor = '#dc3545';
            }
        });

        if (!valid) {
            e.preventDefault();
            alert('Пожалуйста, проверьте заполнение всех полей.');
        }
    });

    // Сброс стилей при очистке формы
    const resetBtn = contactForm.querySelector('button[type="reset"]');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            const fields = contactForm.querySelectorAll('input, textarea');
            fields.forEach(field => {
                field.style.borderColor = '';
            });

            const counters = contactForm.querySelectorAll('.char-counter');
            counters.forEach(counter => {
                counter.classList.remove('warning');
            });
        });
    }
}

// Универсальная функция для счетчиков символов (может использоваться на других страницах)
function initCharCounters() {
    // Находим все поля с атрибутом maxlength и создаем для них счетчики
    const fieldsWithMaxLength = document.querySelectorAll('input[maxlength], textarea[maxlength]');

    fieldsWithMaxLength.forEach(field => {
        const maxLength = parseInt(field.getAttribute('maxlength'));
        const fieldId = field.id;

        // Создаем элемент счетчика, если его еще нет
        if (!document.getElementById(fieldId + '_counter')) {
            const counter = document.createElement('div');
            counter.id = fieldId + '_counter';
            counter.className = 'char-counter';
            counter.textContent = `0/${maxLength}`;

            // Вставляем счетчик после поля
            field.parentNode.insertBefore(counter, field.nextSibling);
        }

        // Настраиваем обновление счетчика
        const counter = document.getElementById(fieldId + '_counter');
        field.addEventListener('input', function() {
            const length = this.value.length;
            counter.textContent = `${length}/${maxLength}`;

            if (length > maxLength * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }

            // Автоматическое обрезание при превышении лимита
            if (length > maxLength) {
                this.value = this.value.substring(0, maxLength);
                counter.textContent = `${maxLength}/${maxLength}`;
            }
        });

        // Инициализируем начальное значение
        const initialLength = field.value.length;
        counter.textContent = `${initialLength}/${maxLength}`;
        if (initialLength > maxLength * 0.9) {
            counter.classList.add('warning');
        }
    });
}