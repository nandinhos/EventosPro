<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota de Débito - {{ $gig->event_name ?? "Gig #{$gig->id}" }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Figtree', 'sans-serif'],
                    },
                    colors: {
                        gray: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280',
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Figtree:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Figtree', sans-serif;
            background-color: #525252;
            color: #374151;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        /* Campos Editáveis Padronizados */
        .editable {
            background: transparent;
            border: 1px solid transparent;
            border-radius: 0.375rem;
            width: 100%;
            transition: all 0.2s;
            padding: 0.25rem 0.5rem;
            line-height: 1.25;
        }

        .editable:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }

        .editable:focus {
            outline: none;
            background-color: #ffffff;
            border-color: #6366f1;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }

        /* CONFIGURAÇÃO A4 PADRÃO ÚNICO */
        .page-sheet {
            width: 210mm;
            height: 297mm;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 10mm 15mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        /* Ocultar elementos na impressão */
        @media print {
            @page {
                size: A4;
                margin: 0;
            }

            body {
                background: white;
                margin: 0;
                padding: 0;
                display: block;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .page-sheet {
                box-shadow: none !important;
                margin: 0 !important;
                border: none !important;
                width: 210mm !important;
                height: 297mm !important;
                padding: 10mm 15mm !important;
                overflow: hidden !important;
                page-break-after: avoid;
            }

            .bg-gray-800 {
                background-color: #1f2937 !important;
                color: white !important;
            }

            .bg-gray-50 {
                background-color: #f9fafb !important;
            }
        }

        .logo-text {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.05em;
        }
    </style>
</head>

<body>

    <!-- Toolbar -->
    <div class="fixed top-5 right-5 flex flex-col gap-2 no-print z-50">
        <button onclick="window.print()"
            class="bg-gray-800 text-white p-3 rounded-full shadow-lg hover:bg-gray-700 transition-all flex items-center justify-center group"
            title="Imprimir / Salvar PDF">
            <i data-lucide="printer" class="w-6 h-6"></i>
        </button>
        <a href="{{ url()->previous() }}"
            class="bg-white text-gray-700 border border-gray-200 p-3 rounded-full shadow-lg hover:bg-gray-50 transition-all flex items-center justify-center group"
            title="Voltar">
            <i data-lucide="arrow-left" class="w-6 h-6"></i>
        </a>
    </div>

    <div class="page-sheet">

        <!-- HEADER -->
        <header class="border-b border-gray-200 pb-4 mb-4">
            <!-- Linha 1: Logo + Título + Número -->
            <div class="flex justify-between items-center mb-4">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('img/coral_360_logo.png') }}" alt="Logo" class="h-32" onerror="this.style.display='none'">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 uppercase tracking-tight">Nota de Débito</h1>
                        <p class="text-xs text-gray-500">Fatura Nº: <span class="font-bold text-gray-900">{{ $debitNote->number }}</span></p>
                    </div>
                </div>
                <div class="text-right">
                    <table class="text-xs ml-auto">
                        <tr>
                            <td class="font-medium text-gray-500 py-0.5 pr-3 text-right">Emissão:</td>
                            <td class="py-0.5 w-28"><input type="date" class="editable text-right text-gray-900 w-full" value="{{ $debitNote->issued_at->format('Y-m-d') }}" readonly></td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-500 py-0.5 pr-3 text-right">Vencimento:</td>
                            <td class="py-0.5 w-28"><input type="date" class="editable text-right text-gray-900 font-semibold w-full" value="{{ date('Y-m-d', strtotime('+3 days')) }}"></td>
                        </tr>
                        <tr>
                            <td class="font-medium text-gray-500 py-0.5 pr-3 text-right">Competência:</td>
                            <td class="py-0.5 w-28"><input type="text" class="editable text-right text-gray-700 w-full pr-6" value="{{ $gig->gig_date?->format('m/Y') }}"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Linha 2: Dados da Empresa -->
            <div class="text-[11px] text-gray-600 leading-tight">
                <p class="font-bold text-gray-900 uppercase text-xs">{{ config('app.company_name', 'CORAL 360 LTDA - EPP') }}</p>
                <p>{{ config('app.company_address', 'Rod. D. Pedro I, S/N, SL 02 - Santana dos Cuiaban') }} - {{ config('app.company_city', 'Valinhos - SP') }} | CEP: {{ config('app.company_postal', '13273-310') }} | CNPJ: {{ config('app.company_cnpj', '52.507.002/0001-75') }}</p>
            </div>
        </header>

        <!-- CLIENTE (BILL TO) Compacto -->
        <section class="bg-gray-50 p-3 rounded-lg border border-gray-200 mb-4">
            <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 flex items-center gap-1.5">
                <i data-lucide="user" class="w-3 h-3"></i> Tomador dos Serviços (Cliente)
            </h3>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs">
                <div class="flex items-center">
                    <span class="font-medium text-gray-500 mr-1">Razão:</span>
                    <span class="font-bold text-gray-900">{{ $serviceTaker->organization ?? '' }}</span>
                    <span class="mx-2 text-gray-400">-</span>
                    <span class="font-medium text-gray-500 mr-1">Ref/Evento:</span>
                    <span class="text-gray-700">{{ $gig->location_event_details ?? $gig->event_name ?? "Gig #{$gig->id}" }}</span>
                </div>
                <div class="w-full flex gap-x-6 mt-1">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-500 mr-1">DOC:</span>
                        <span class="text-gray-700">{{ $serviceTaker->document ?? '' }}</span>
                    </div>
                    <div class="flex items-center flex-1">
                        <span class="font-medium text-gray-500 mr-1">Endereço:</span>
                        <span class="text-gray-700">{{ $serviceTaker->full_address }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- DETAILED ITEMS -->
        <section class="mb-4 flex-grow overflow-hidden flex flex-col">
            <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 pl-1">Discriminativo de Serviços e Despesas</h3>
            <div class="border border-gray-200 rounded-lg overflow-visible flex-grow">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="bg-gray-50 text-gray-700 text-[10px] uppercase tracking-wider border-b border-gray-200">
                            <th class="py-2 px-3 text-left font-semibold">Descrição / Histórico</th>
                            <th class="py-2 px-3 text-center w-20 font-semibold">Qtd/Ref.</th>
                            <th class="py-2 px-3 text-right w-24 font-semibold">Cachê Artista (R$)</th>
                            <th class="py-2 px-3 text-right w-24 font-semibold">Despesas (R$)</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceItems" class="divide-y divide-gray-200">
                        <!-- Row 1 - Cachê do Artista -->
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="p-2 align-top">
                                <div class="flex flex-col gap-1">
                                    <input type="text" class="editable font-semibold text-gray-900 w-full"
                                        value="Cachê Artista - {{ $gig->artist->name ?? 'Artista' }}">
                                    <textarea class="editable text-[10px] text-gray-500 w-full h-20 resize-none leading-relaxed">Pagamento de cachê artístico conforme contrato.
Evento: {{ $gig->location_event_details ?? '' }}
Data: {{ $gig->gig_date?->format('d/m/Y') }}</textarea>
                                </div>
                            </td>
                            <td class="p-2 align-top"><input type="text" class="editable text-center text-gray-700 w-full" value="1.0"></td>
                            <td class="p-2 align-top"><input type="text" class="editable text-right fees-col text-gray-900 w-full font-medium"
                                    value="{{ number_format($settlement?->artist_payment_value ?? $honorarios, 2, ',', '.') }}" oninput="formatAndCalc(this)"></td>
                            <td class="p-2 align-top"><input type="text" class="editable text-right exp-col bg-gray-50 text-gray-400 w-full"
                                    value="0,00" disabled></td>
                        </tr>
                        
                        @if($despesasItens->count() > 0)
                        @php
                            // Agrupar despesas por centro de custo e calcular total
                            $despesasPorCentro = $despesasItens->groupBy(fn($d) => $d->costCenter->name ?? 'Outros');
                            $totalDespesasReembolsaveis = $despesasItens->sum('value');
                        @endphp
                        <!-- Row 2 - Despesas Reembolsáveis -->
                        <tr class="group hover:bg-gray-50 transition-colors">
                            <td class="p-2 align-top">
                                <div class="flex flex-col gap-1">
                                    <input type="text" class="editable font-semibold text-gray-900 w-full"
                                        value="Reembolso: Despesas do Evento">
                                    <textarea class="editable text-[10px] text-gray-500 w-full h-20 resize-none leading-relaxed">@foreach($despesasPorCentro as $centro => $itens)
{{ $centro }}: @foreach($itens as $d){{ $d->description }} (R$ {{ number_format($d->value, 2, ',', '.') }})@if(!$loop->last), @endif @endforeach
@endforeach</textarea>
                                </div>
                            </td>
                            <td class="p-2 align-top"><input type="text" class="editable text-center text-gray-700 w-full" value="{{ $despesasItens->count() }}"></td>
                            <td class="p-2 align-top"><input type="text" class="editable text-right fees-col bg-gray-50 text-gray-400 w-full" value="0,00" disabled></td>
                            <td class="p-2 align-top"><input type="text" class="editable text-right exp-col text-gray-900 w-full font-medium" 
                                    value="{{ number_format($totalDespesasReembolsaveis, 2, ',', '.') }}" oninput="formatAndCalc(this)"></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-2 no-print">
                <button onclick="addItem()"
                    class="text-[10px] text-gray-600 hover:text-gray-900 hover:bg-gray-100 px-3 py-2 rounded flex items-center gap-1 font-medium transition-colors">
                    <i data-lucide="plus" class="w-3 h-3"></i> Adicionar Linha
                </button>
            </div>
        </section>

        <!-- FINANCIAL DASHBOARD (Compact) -->
        <div class="grid grid-cols-2 gap-4 mb-4 break-inside-avoid h-auto">

            <!-- TAX RETENTION BLOCK (Esquerda) -->
            <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm h-full flex flex-col justify-center">
                <h3 class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 border-b border-gray-100 pb-1">
                    Retenções Legais (Lei 10.833/03)
                </h3>
                <table class="w-full text-[10px]">
                    <tr class="text-gray-500 text-right">
                        <th class="text-left pb-1 font-medium">Imposto</th>
                        <th class="pb-1 w-12 font-medium">%</th>
                        <th class="pb-1 w-16 font-medium">Valor (R$)</th>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-gray-700">IRRF</td>
                        <td><input type="text" class="editable text-right text-gray-600" value="0,00%" onchange="calcTaxes()"></td>
                        <td><input type="text" id="val_irrf" class="editable text-right font-medium text-gray-900" value="0,00"></td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-gray-700">PIS/COFINS/CSLL</td>
                        <td><input type="text" class="editable text-right text-gray-600" value="0,00%" onchange="calcTaxes()"></td>
                        <td><input type="text" id="val_pcc" class="editable text-right font-medium text-gray-900" value="0,00"></td>
                    </tr>
                    <tr>
                        <td class="py-0.5 text-gray-400">ISS (Retido)</td>
                        <td><input type="text" class="editable text-right text-gray-400" value="0,00%" onchange="calcTaxes()"></td>
                        <td><input type="text" id="val_iss" class="editable text-right font-medium text-gray-900" value="0,00"></td>
                    </tr>
                </table>
                <p class="text-[9px] text-gray-400 mt-1 italic leading-tight">* Responsabilidade do tomador confirmar as alíquotas de retenção.</p>
            </div>

            <!-- TOTALS BLOCK (Direita) -->
            @php
                $cacheArtista = $settlement->artist_payment_value ?? $honorarios;
                $totalReembolsos = $despesasItens->sum('value');
                $valorLiquido = $cacheArtista + $totalReembolsos;
            @endphp
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 flex flex-col justify-center">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600">(+) Total Cachê Artista</span>
                    <span class="font-semibold text-gray-900" id="total_fees">{{ number_format($cacheArtista, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-gray-600">(+) Total Reembolsos</span>
                    <span class="font-semibold text-gray-900" id="total_exp">{{ number_format($totalReembolsos, 2, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-xs mb-2 text-gray-600">
                    <span>(-) Total Retenções</span>
                    <span class="font-semibold text-gray-900" id="total_tax">0,00</span>
                </div>
                <div class="border-t border-gray-300 pt-2 flex justify-between items-center">
                    <span class="font-bold text-gray-800 uppercase text-[10px] tracking-wide">Valor Líquido a Pagar</span>
                    <span class="font-bold text-xl text-gray-900">R$ <span id="net_total">{{ number_format($valorLiquido, 2, ',', '.') }}</span></span>
                </div>
            </div>
        </div>

        <!-- FOOTER: Bank & Signatures -->
        <div class="mt-auto pt-3 border-t border-gray-200 break-inside-avoid">
            <div class="flex gap-6">
                <div class="w-1/2">
                    <h4 class="font-bold text-[10px] uppercase mb-1 text-gray-500 tracking-wider">Dados Bancários</h4>
                    <div class="text-[10px] space-y-0.5 bg-gray-50 p-2 rounded border border-gray-200 text-gray-700">
                        <div class="flex items-center"><span class="w-16 font-semibold text-gray-900">Banco:</span>
                            <input type="text" class="editable bg-transparent p-0 h-auto w-full" value="{{ config('app.bank_name', 'Banco do Brasil') }}">
                        </div>
                        <div class="flex items-center"><span class="w-16 font-semibold text-gray-900">Agência:</span>
                            <input type="text" class="editable bg-transparent p-0 h-auto w-full" value="{{ config('app.bank_agency', '0001') }}">
                        </div>
                        <div class="flex items-center"><span class="w-16 font-semibold text-gray-900">C/C:</span>
                            <input type="text" class="editable bg-transparent p-0 h-auto w-full" value="{{ config('app.bank_account', '69349-7') }}">
                        </div>
                        <div class="flex items-center"><span class="w-16 font-semibold text-gray-900">PIX:</span>
                            <input type="text" class="editable bg-transparent p-0 h-auto w-full" value="{{ config('app.company_cnpj', '52.507.002/0001-75') }}">
                        </div>
                    </div>
                </div>
                <div class="w-1/2 flex flex-col justify-end">
                    <div class="text-center pb-1">
                        <div class="border-b border-gray-900 w-3/4 mx-auto mb-1"></div>
                        <p class="font-bold text-[10px] uppercase text-gray-900">{{ config('app.company_name', 'CORAL 360 LTDA - EPP') }}</p>
                        <p class="text-[9px] text-gray-500 uppercase tracking-wide">Departamento Financeiro</p>
                    </div>
                </div>
            </div>
            <p class="text-[9px] text-center text-gray-400 mt-2">Atividade: Serviços de organização de feiras, congressos, exposições e festas (CNAE 8230-0/01)</p>
        </div>

    </div>

    <script>
        // Init
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });

        // Formatação de Moeda e Cálculo em tempo real
        function formatAndCalc(input) {
            let value = input.value.replace(/\D/g, "");
            value = (value / 100).toFixed(2) + "";
            value = value.replace(".", ",");
            value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
            input.value = value;
            calcTaxes();
        }

        // Parse moeda BR para Float
        function parseBRL(val) {
            if (!val) return 0;
            return parseFloat(val.replace(/\./g, '').replace(',', '.')) || 0;
        }

        // Formata Float para moeda BR
        function toBRL(val) {
            return val.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function calcTaxes() {
            let totalFees = 0;
            let totalExp = 0;

            // Soma colunas
            document.querySelectorAll('.fees-col').forEach(el => totalFees += parseBRL(el.value));
            document.querySelectorAll('.exp-col').forEach(el => totalExp += parseBRL(el.value));

            // Atualiza Totais Brutos
            document.getElementById('total_fees').innerText = toBRL(totalFees);
            document.getElementById('total_exp').innerText = toBRL(totalExp);

            // Calcula Impostos (Base é geralmente apenas Honorários, não Despesas)
            const inputs = document.querySelectorAll('table input[value*="%"]');
            let taxTotal = 0;

            // IRRF
            let irrfRate = parseFloat(inputs[0].value.replace('%', '').replace(',', '.')) || 0;
            let irrfVal = (totalFees * (irrfRate / 100));
            document.getElementById('val_irrf').value = toBRL(irrfVal);
            taxTotal += irrfVal;

            // PIS/COFINS/CSLL (PCC)
            let pccRate = parseFloat(inputs[1].value.replace('%', '').replace(',', '.')) || 0;
            let pccVal = (totalFees * (pccRate / 100));
            document.getElementById('val_pcc').value = toBRL(pccVal);
            taxTotal += pccVal;

            // ISS
            let issRate = parseFloat(inputs[2].value.replace('%', '').replace(',', '.')) || 0;
            let issVal = (totalFees * (issRate / 100));
            document.getElementById('val_iss').value = toBRL(issVal);
            taxTotal += issVal;

            // Atualiza Total Retenções e Líquido
            document.getElementById('total_tax').innerText = toBRL(taxTotal);

            let net = (totalFees + totalExp) - taxTotal;
            document.getElementById('net_total').innerText = toBRL(net);
        }

        function addItem() {
            const tbody = document.getElementById('invoiceItems');
            const row = document.createElement('tr');
            row.className = 'group hover:bg-gray-50 transition-colors item-row';
            row.innerHTML = `
                <td class="p-2 align-top">
                    <div class="flex flex-col gap-1">
                        <input type="text" class="editable font-semibold text-gray-900 w-full" placeholder="Descrição">
                        <textarea class="editable text-[10px] text-gray-500 w-full h-8 resize-none leading-relaxed" placeholder="Detalhes..."></textarea>
                    </div>
                </td>
                <td class="p-2 align-top"><input type="text" class="editable text-center text-gray-700 w-full" value="1.0"></td>
                <td class="p-2 align-top"><input type="text" class="editable text-right fees-col text-gray-900 w-full font-medium" value="0,00" oninput="formatAndCalc(this)"></td>
                <td class="p-2 align-top"><input type="text" class="editable text-right exp-col text-gray-900 w-full font-medium" value="0,00" oninput="formatAndCalc(this)"></td>
                <td class="no-print text-center align-top pt-2">
                    <button onclick="delRow(this)" class="text-gray-400 hover:text-red-600 transition-colors p-1.5 rounded">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
            if (window.lucide) lucide.createIcons();
        }

        function delRow(btn) {
            const row = btn.closest('tr');
            if (document.querySelectorAll('#invoiceItems tr').length > 1) {
                row.remove();
                calcTaxes();
            }
        }
    </script>
</body>

</html>
