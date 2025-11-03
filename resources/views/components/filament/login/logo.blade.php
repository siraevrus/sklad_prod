<div class="filament-login-logo" style="height: 14.56rem !important; display: flex; align-items: center; justify-content: center;">
    <img 
        src="{{ asset('logo-expertwood.svg') }}" 
        alt="Логотип WOOD WAREHOUSE" 
        style="height: 14.56rem !important; max-width: 100%; display: block;"
        class="fi-logo"
    >
</div>

<script>
    // Переопределяем высоту логотипа на странице авторизации
    document.addEventListener('DOMContentLoaded', function() {
        const logo = document.querySelector('.filament-login-logo img');
        if (logo) {
            logo.style.height = '14.56rem !important';
            logo.style.setProperty('height', '14.56rem', 'important');
        }
    });
    
    // Также следим за изменениями
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.target.classList?.contains('fi-logo')) {
                mutation.target.style.height = '14.56rem !important';
                mutation.target.style.setProperty('height', '14.56rem', 'important');
            }
        });
    });
    
    const config = { attributes: true, subtree: true, attributeFilter: ['style'] };
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.filament-login-logo');
        if (container) observer.observe(container, config);
    });
</script>
