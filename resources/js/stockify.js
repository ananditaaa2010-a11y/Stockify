import 'flowbite';

const loader = document.getElementById('page-loader');

function hideLoader() {
    if (!loader) {
        return;
    }

    loader.classList.add('pointer-events-none', 'opacity-0');
    window.setTimeout(() => loader.classList.add('hidden'), 300);
}

function showLoader() {
    if (!loader) {
        return;
    }

    loader.classList.remove('hidden', 'pointer-events-none', 'opacity-0');
}

window.addEventListener('load', () => window.setTimeout(hideLoader, 300), { once: true });

document.querySelectorAll('[data-loading-link]').forEach((link) => {
    link.addEventListener('click', (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        showLoader();
    });
});

document.querySelectorAll('[data-loading-form]').forEach((form) => {
    form.addEventListener('submit', showLoader);
});

document.querySelectorAll('[data-file-name]').forEach((input) => {
    const display = input.parentElement.querySelector('[data-file-name-display]');

    input.addEventListener('change', () => {
        if (!display) {
            throw new Error('Label nama file yang dipilih tidak tersedia.');
        }

        display.textContent = input.files?.[0]?.name ?? 'Belum ada file dipilih';
    });
});

document.querySelectorAll('[data-payment-form]').forEach((form) => {
    const methods = [...form.querySelectorAll('[data-payment-method]')];
    const referenceLabel = form.querySelector('[data-payment-reference-label]');
    const referenceInput = form.querySelector('[data-payment-reference]');
    const referenceHelp = form.querySelector('[data-payment-reference-help]');
    const proofContainer = form.querySelector('[data-payment-proof-container]');
    const paymentDestination = form.querySelector('[data-payment-destination]');
    const addressContainer = form.querySelector('[data-delivery-address-container]');
    const addressInput = form.querySelector('[data-delivery-address]');
    const proofInput = proofContainer?.querySelector('input[type="file"]');

    if (!methods.length || !referenceLabel || !referenceInput || !referenceHelp || !proofContainer || !paymentDestination || !addressContainer || !addressInput || !proofInput) {
        throw new Error('Field metode pembayaran tidak lengkap.');
    }

    const updatePaymentFields = () => {
        const selectedMethod = methods.find((input) => input.checked)?.value;
        const isTransfer = selectedMethod === 'Transfer Bank';
        const isCod = selectedMethod === 'COD';

        referenceLabel.textContent = isTransfer ? 'Nomor referensi transaksi' : 'Catatan pembayaran (opsional)';
        referenceInput.required = isTransfer;
        referenceInput.placeholder = isTransfer ? 'Masukkan nomor referensi transaksi' : 'Contoh: bayar saat pesanan diterima';
        referenceHelp.textContent = isTransfer
            ? 'Isi nomor referensi/ID transaksi dari aplikasi setelah transfer. Angka tujuan di atas hanya contoh, bukan rekening sungguhan.'
            : isCod
                ? 'Catatan boleh dikosongkan; pesanan akan dicatat sebagai pembayaran tunai saat diterima.'
                : 'Catatan boleh dikosongkan; pesanan akan dicatat sebagai pembayaran tunai saat pengambilan.';
        paymentDestination.classList.toggle('hidden', !isTransfer);
        proofContainer.classList.toggle('hidden', !isTransfer);
        proofInput.disabled = !isTransfer;
        addressContainer.classList.toggle('hidden', !isCod);
        addressInput.required = isCod;
        addressInput.disabled = !isCod;
    };

    methods.forEach((method) => method.addEventListener('change', updatePaymentFields));
    updatePaymentFields();
});

document.querySelectorAll('[data-smooth-scroll]').forEach((link) => {
    link.addEventListener('click', (event) => {
        const target = document.querySelector(link.getAttribute('href'));

        if (!target) {
            return;
        }

        event.preventDefault();
        target.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
            block: 'start',
        });
        window.history.replaceState(null, '', link.getAttribute('href'));
    });
});

const productSearch = document.querySelector('[data-product-search]');

if (productSearch) {
    const productRows = [...document.querySelectorAll('[data-product-row]')];
    const emptyResult = document.getElementById('products-no-search-results');

    productSearch.addEventListener('input', () => {
        const query = productSearch.value.trim().toLocaleLowerCase();
        let visibleProducts = 0;

        productRows.forEach((row) => {
            const matches = row.textContent.toLocaleLowerCase().includes(query);
            row.classList.toggle('hidden', !matches);
            visibleProducts += Number(matches);
        });

        emptyResult?.classList.toggle('hidden', visibleProducts > 0);
    });
}

const attributeList = document.querySelector('[data-attribute-list]');
const addAttributeButton = document.querySelector('[data-add-attribute]');

if (attributeList && addAttributeButton) {
    let nextAttributeIndex = attributeList.querySelectorAll('[data-remove-attribute]').length;
    const attributeTemplate = document.querySelector('[data-attribute-row-template]');

    addAttributeButton.addEventListener('click', () => {
        if (!attributeTemplate) {
            throw new Error('Template baris atribut produk tidak tersedia.');
        }

        const index = nextAttributeIndex;
        nextAttributeIndex += 1;
        const row = attributeTemplate.content.cloneNode(true);
        row.querySelectorAll('[name]').forEach((field) => {
            field.name = field.name.replace('__INDEX__', index);
        });
        attributeList.append(row);
    });

    attributeList.addEventListener('click', (event) => {
        if (event.target.closest('[data-remove-attribute]')) {
            const rows = attributeList.querySelectorAll('[data-remove-attribute]');
            if (rows.length > 1) {
                event.target.closest('[data-remove-attribute]').parentElement.remove();
            }
        }
    });
}

document.querySelectorAll('input[type="password"]').forEach((input) => {
    if (input.parentElement?.dataset.passwordVisibilityWrapper === 'true') {
        return;
    }

    const wrapper = document.createElement('span');
    wrapper.className = 'relative block';
    wrapper.dataset.passwordVisibilityWrapper = 'true';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input);
    input.classList.add('pr-12');

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-teal-800 focus:outline-none focus:ring-0';

    const eyeIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.5 12s3.3-6 9.5-6 9.5 6 9.5 6-3.3 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
    const hiddenEyeIcon = '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.6 6.2A10.7 10.7 0 0 1 12 6c6.2 0 9.5 6 9.5 6a15.8 15.8 0 0 1-3.1 3.7M6.2 6.2C3.8 7.8 2.5 12 2.5 12s3.3 6 9.5 6a9.8 9.8 0 0 0 2.7-.4"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
    toggle.innerHTML = hiddenEyeIcon;
    toggle.setAttribute('aria-label', 'Lihat kata sandi');
    toggle.setAttribute('aria-pressed', 'false');
    toggle.addEventListener('click', () => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        toggle.innerHTML = visible ? eyeIcon : hiddenEyeIcon;
        toggle.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Lihat kata sandi');
        toggle.setAttribute('aria-pressed', String(visible));
    });
    wrapper.append(toggle);
});

const sidebar = document.getElementById('sidebar');
const sidebarToggles = [...document.querySelectorAll('[data-sidebar-toggle]')];
const sidebarBackdrop = document.getElementById('sidebar-backdrop');

if (sidebar && sidebarToggles.length && sidebarBackdrop) {
    const desktop = window.matchMedia('(min-width: 1024px)');
    const syncSidebarToggleState = (expanded) => {
        sidebarToggles.forEach((toggle) => toggle.setAttribute('aria-expanded', String(expanded)));
    };

    if (desktop.matches && localStorage.getItem('stockify-sidebar-collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
        syncSidebarToggleState(false);
    } else if (!desktop.matches) {
        syncSidebarToggleState(false);
    }

    sidebarToggles.forEach((toggle) => toggle.addEventListener('click', () => {
        if (desktop.matches) {
            const collapsed = document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('stockify-sidebar-collapsed', String(collapsed));
            syncSidebarToggleState(!collapsed);
        } else {
            const open = sidebar.classList.toggle('translate-x-0');
            sidebarBackdrop.classList.toggle('hidden', !open);
            syncSidebarToggleState(open);
        }
    }));

    sidebar.querySelectorAll('nav a').forEach((link) => link.addEventListener('click', () => {
        if (!desktop.matches) {
            sidebar.classList.remove('translate-x-0');
            sidebarBackdrop.classList.add('hidden');
            syncSidebarToggleState(false);
        }
    }));

    sidebarBackdrop.addEventListener('click', () => {
        sidebar.classList.remove('translate-x-0');
        sidebarBackdrop.classList.add('hidden');
        syncSidebarToggleState(false);
    });
}

async function renderStockChart() {
    const chartElement = document.getElementById('stock-chart');

    if (!chartElement) {
        return;
    }

    const { default: ApexCharts } = await import('apexcharts');
    const labels = JSON.parse(chartElement.dataset.labels || '[]');
    const incoming = JSON.parse(chartElement.dataset.incoming || '[]');
    const outgoing = JSON.parse(chartElement.dataset.outgoing || '[]');

    const chart = new ApexCharts(chartElement, {
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            fontFamily: 'inherit',
            animations: { enabled: true, easing: 'easeinout', speed: 450 },
        },
        series: [
            { name: 'Barang masuk', data: incoming },
            { name: 'Barang keluar', data: outgoing },
        ],
        colors: ['#8872dd', '#79bd98'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2.5 },
        fill: {
            type: 'gradient',
            gradient: { opacityFrom: 0.2, opacityTo: 0.02, stops: [0, 90, 100] },
        },
        grid: { borderColor: '#ece9f4', strokeDashArray: 4, padding: { left: 8, right: 8 } },
        xaxis: {
            categories: labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: '#858098', fontSize: '12px' } },
        },
        yaxis: {
            min: 0,
            forceNiceScale: true,
            labels: { style: { colors: '#858098', fontSize: '12px' } },
        },
        legend: { show: false },
        tooltip: {
            shared: true,
            intersect: false,
            y: { formatter: (value) => `${new Intl.NumberFormat('id-ID').format(value)} unit` },
        },
        noData: { text: 'Belum ada data transaksi stok.' },
    });

    chart.render();
}

renderStockChart();
