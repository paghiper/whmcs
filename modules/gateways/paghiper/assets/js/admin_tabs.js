document.addEventListener("DOMContentLoaded", function () {
    if (window.paghiperTabsInitialized) return;
    window.paghiperTabsInitialized = true;

    var isPaghiper = false;
    var forms = document.querySelectorAll("form");
    forms.forEach(function (f) {
        if (f.innerHTML.indexOf('name="field[email]"') !== -1 && f.innerHTML.indexOf('paghiper') !== -1) {
            isPaghiper = true;
            initPaghiperTabs(f);
        }
    });

    function initPaghiperTabs(form) {
        if (form.dataset.paghiperInit) return;
        form.dataset.paghiperInit = "1";

        var table = form.querySelector("table.form");
        if (!table) return;

        var groups = {
            "Geral": ["nota", "FriendlyName", "email", "api_key", "token", "cpf_cnpj", "razao_social", "admin", "suporte"],
            "Taxas e Prazos": ["porcento", "taxa", "open_after_day_due", "reissue_unpaid", "late_payment_fine", "per_day_interest", "early_payment_discounts_days", "early_payment_discounts_cents"],
            "Integração": ["ui_email_templates", "ui_injector"],
            "Avançado": ["issue_all", "tax_id_validation", "abrirauto", "fixed_description", "negar_sem_company_razao"]
        };

        var tabContainer = document.createElement("ul");
        tabContainer.className = "nav nav-tabs admin-tabs";
        tabContainer.style.marginBottom = "15px";

        var first = true;
        for (var group in groups) {
            var li = document.createElement("li");
            if (first) li.className = "active";

            var a = document.createElement("a");
            a.href = "#";
            a.innerHTML = group;
            a.onclick = (function (activeGroup, tabLink) {
                return function (e) {
                    e.preventDefault();

                    var tabs = tabContainer.querySelectorAll("li");
                    tabs.forEach(function (t) { t.className = ""; });
                    tabLink.parentNode.className = "active";

                    var rows = table.querySelectorAll("tr");
                    rows.forEach(function (row) {
                        var input = row.querySelector("[name^='field[']");
                        if (input) {
                            var fieldNameMatch = input.name.match(/field\[(.*?)\]/);
                            if (fieldNameMatch) {
                                var fieldName = fieldNameMatch[1];
                                if (groups[activeGroup].indexOf(fieldName) !== -1) {
                                    row.style.display = "";
                                } else {
                                    row.style.display = "none";
                                }
                            }
                        } else {
                            // Handle pseudo-fields with wrappers
                            if (row.querySelector('#paghiper_row_nota')) { row.style.display = (activeGroup === 'Geral') ? '' : 'none'; }
                            if (row.querySelector('#paghiper_row_suporte')) { row.style.display = (activeGroup === 'Geral') ? '' : 'none'; }
                            if (row.querySelector('#paghiper_row_ui_injector')) { row.style.display = (activeGroup === 'Integração') ? '' : 'none'; }
                            if (row.querySelector('#paghiper_row_ui_email_templates')) { row.style.display = (activeGroup === 'Integração') ? '' : 'none'; }
                        }
                    });
                };
            })(group, a);

            li.appendChild(a);
            tabContainer.appendChild(li);

            // Initial render
            if (first) {
                var rows = table.querySelectorAll("tr");
                rows.forEach(function (row) {
                    var input = row.querySelector("[name^='field[']");
                    if (input) {
                        var fieldNameMatch = input.name.match(/field\[(.*?)\]/);
                        if (fieldNameMatch) {
                            var fieldName = fieldNameMatch[1];
                            if (groups[group].indexOf(fieldName) !== -1) {
                                row.style.display = "";
                            } else {
                                row.style.display = "none";
                            }
                        }
                    } else {
                        // Handle pseudo-fields with wrappers
                        if (row.querySelector('#paghiper_row_nota')) { row.style.display = (group === 'Geral') ? '' : 'none'; }
                        if (row.querySelector('#paghiper_row_suporte')) { row.style.display = (group === 'Geral') ? '' : 'none'; }
                        if (row.querySelector('#paghiper_row_ui_injector')) { row.style.display = (group === 'Integração') ? '' : 'none'; }
                        if (row.querySelector('#paghiper_row_ui_email_templates')) { row.style.display = (group === 'Integração') ? '' : 'none'; }
                    }
                });
            }
            first = false;
        }

        table.parentNode.insertBefore(tabContainer, table);

        setupIntegrationUI(form);
        setupEmailTemplatesUI(form);
    }

    function setupEmailTemplatesUI(form) {
        var hiddenRow = form.querySelector('#paghiper_row_email_templates_hidden');
        if (!hiddenRow) return;

        var parentTr = hiddenRow.closest('tr');
        if (parentTr) parentTr.style.display = 'none';

        var inputEl = form.querySelector('input[name="field[email_templates]"]');
        if (!inputEl) return;

        var uiContainer = form.querySelector('#paghiper_row_ui_email_templates');
        if (!uiContainer) return;

        var availableTemplates = [
            'Invoice Created',
            'Invoice Payment Reminder',
            'First Invoice Overdue Notice',
            'Second Invoice Overdue Notice',
            'Third Invoice Overdue Notice'
        ];

        var currentValues = inputEl.value.split(',').map(s => s.trim()).filter(s => s !== '');

        var html = '<div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px;">';
        html += '<p>Selecione os e-mails nos quais o boleto ou código PIX serão anexados automaticamente (requer Integração do PDF ativada).</p>';
        html += '<div class="checkbox-list">';

        availableTemplates.forEach(function (tpl) {
            var checked = currentValues.indexOf(tpl) !== -1 ? 'checked' : '';
            var safeTpl = tpl.replace(/[^a-zA-Z0-9]/g, '');
            html += '<label style="display:block; margin-bottom:5px; font-weight:normal;">';
            html += '<input type="checkbox" class="paghiper-email-tpl-cb" value="' + tpl + '" ' + checked + '> <strong>' + tpl + '</strong>';
            html += '<span id="paghiper-tpl-status-' + safeTpl + '" style="margin-left:5px; font-size:12px; color:#666;"> - <i>(Analisando...)</i></span>';
            html += '</label>';
        });

        html += '<div style="margin-top:10px;"><button id="paghiper-analyze-emails" class="btn btn-default btn-sm">Atualizar Status</button></div>';

        // Initialize global state if not present
        window.paghiperIssueAllState = window.paghiperIssueAllState || { paghiper: false, paghiper_pix: false };
        
        var friendlyNames = { paghiper: 'PagHiper', paghiper_pix: 'PagHiper PIX' };
        var integrationContainer = form.querySelector('.paghiper-integration-ui-container');
        var currentModule = '';
        if (integrationContainer) {
            currentModule = integrationContainer.getAttribute('data-module') || '';
            try {
                friendlyNames = JSON.parse(integrationContainer.dataset.friendlyNames);
                var dbIssueAll = JSON.parse(integrationContainer.dataset.issueAll);
                // Only merge DB state if we haven't tracked DOM state yet
                if (typeof window.paghiperIssueAllState[currentModule] === 'undefined' || !window.paghiperIssueAllState.initialized) {
                    window.paghiperIssueAllState.paghiper = dbIssueAll.paghiper;
                    window.paghiperIssueAllState.paghiper_pix = dbIssueAll.paghiper_pix;
                    window.paghiperIssueAllState.initialized = true;
                }
            } catch (e) { }
        }

        html += '<p style="margin-top:15px; margin-bottom:5px;"><strong>Dica Avançada:</strong> Se desejar personalizar o corpo do e-mail incluindo os dados de pagamento de forma inteligente, copie e cole o bloco condicional abaixo no seu template (Menu <i>Setup > Email Templates</i>):</p>';
        html += '<pre class="paghiper-smarty-snippet-box" style="background:#fff; padding:10px; border:1px solid #ccc; font-size:11px; margin-bottom:0;"></pre>';

        html += '</div>';
        uiContainer.innerHTML = html;

        function updateSnippetUI() {
            var snippet = '';
            var pixIssueAll = window.paghiperIssueAllState.paghiper_pix;
            var boletoIssueAll = window.paghiperIssueAllState.paghiper;

            var domIssueAll = form.querySelector('input[type="checkbox"][name="field[issue_all]"]');
            if (domIssueAll) {
                if (currentModule === 'paghiper_pix') {
                    pixIssueAll = domIssueAll.checked;
                    window.paghiperIssueAllState.paghiper_pix = domIssueAll.checked;
                } else {
                    boletoIssueAll = domIssueAll.checked;
                    window.paghiperIssueAllState.paghiper = domIssueAll.checked;
                }
            }

            if (pixIssueAll) {
                snippet = '{if $invoice_payment_method eq "' + friendlyNames.paghiper + '"}<br>  {$linha_digitavel}<br>{else}<br>  {$codigo_pix}<br>{/if}';
            } else if (boletoIssueAll) {
                snippet = '{if $invoice_payment_method eq "' + friendlyNames.paghiper_pix + '"}<br>  {$codigo_pix}<br>{else}<br>  {$linha_digitavel}<br>{/if}';
            } else {
                snippet = '{if $invoice_payment_method eq "' + friendlyNames.paghiper_pix + '"}<br>  {$codigo_pix}<br>{elseif $invoice_payment_method eq "' + friendlyNames.paghiper + '"}<br>  {$linha_digitavel}<br>{/if}';
            }

            var box = form.querySelector('.paghiper-smarty-snippet-box');
            if (box) box.innerHTML = snippet;
        }

        updateSnippetUI();

        var domIssueAllCb = form.querySelector('input[type="checkbox"][name="field[issue_all]"]');
        if (domIssueAllCb) {
            // Also sync initial DOM state to global state in case it differs from DB (e.g. user toggled but didn't save)
            if (currentModule === 'paghiper_pix') window.paghiperIssueAllState.paghiper_pix = domIssueAllCb.checked;
            if (currentModule === 'paghiper') window.paghiperIssueAllState.paghiper = domIssueAllCb.checked;

            domIssueAllCb.addEventListener('change', function (e) {
                // Determine if the OTHER module is active
                var otherModuleActive = (currentModule === 'paghiper') ? window.paghiperIssueAllState.paghiper_pix : window.paghiperIssueAllState.paghiper;
                
                updateSnippetUI(); // this will also update window.paghiperIssueAllState with the new checked value

                if (this.checked && otherModuleActive) {
                    if (currentModule === 'paghiper') {
                        alert("Atenção: A emissão global de PIX já está ativa no gateway PagHiper PIX.\n\nComo o PIX tem prioridade absoluta, os boletos não serão gerados (o PIX será gerado no lugar) até que a opção global do PIX seja desativada.");
                    } else if (currentModule === 'paghiper_pix') {
                        alert("Atenção: A emissão global de Boletos já estava ativa no outro gateway.\n\nAo ativar o PIX global agora, ele assume prioridade absoluta e passará a sobrescrever a regra de Boletos.");
                    }
                }
            });
        }

        // Smart submit alert
        form.addEventListener('submit', function (e) {
            var isChecked = domIssueAllCb ? domIssueAllCb.checked : false;
            var otherModuleActive = (currentModule === 'paghiper') ? window.paghiperIssueAllState.paghiper_pix : window.paghiperIssueAllState.paghiper;
            
            if (isChecked && otherModuleActive) {
                if (currentModule === 'paghiper') {
                    alert("Lembrete ao salvar: Você está ativando/mantendo a emissão global de Boletos, mas a de PIX também está ativa. O PIX terá prioridade!");
                } else if (currentModule === 'paghiper_pix') {
                    alert("Lembrete ao salvar: Você está ativando/mantendo a emissão global de PIX. Ela terá prioridade absoluta sobre a emissão global de Boletos que já estava ativa.");
                }
            }
        });

        // Listen to changes and update the hidden text input
        var checkboxes = uiContainer.querySelectorAll('.paghiper-email-tpl-cb');
        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', function () {
                var selected = [];
                uiContainer.querySelectorAll('.paghiper-email-tpl-cb:checked').forEach(function (checkedCb) {
                    selected.push(checkedCb.value);
                });
                inputEl.value = selected.join(',');
            });
        });

        function runAnalysis() {
            var moduleName = 'paghiper';
            if (window.location.href.indexOf('paghiper_pix') !== -1 || form.innerHTML.indexOf('Frase fixa no PIX') !== -1 || form.innerHTML.indexOf('PAGHIPER PIX') !== -1) {
                moduleName = 'paghiper_pix';
            }
            var mInput = form.querySelector('input[name="module"]');
            if (mInput && mInput.value) { moduleName = mInput.value; }

            var allTpls = availableTemplates.join(',');

            submitAjaxAction('analyze_email_templates', { templates: allTpls, module: moduleName }, function (res) {
                if (res.success && res.analysis) {
                    availableTemplates.forEach(function (tpl) {
                        var safeTpl = tpl.replace(/[^a-zA-Z0-9]/g, '');
                        var span = form.querySelector('#paghiper-tpl-status-' + safeTpl);
                        if (span && res.analysis[tpl]) {
                            var langs = res.analysis[tpl];
                            if (langs.length === 0) {
                                span.innerHTML = ' | <span style="color:#999;">Template não encontrado</span>';
                            } else if (langs.length === 1) {
                                var l = langs[0];
                                var mark = l.integrated ? '<span style="color:green;"><i class="fa fa-check"></i> Integrado</span>' : '<span style="color:red;"><i class="fa fa-times"></i> Não integrado</span>';
                                span.innerHTML = ' | ' + mark + ' - <a href="configemailtemplates.php?action=edit&id=' + l.id + '" target="_blank" style="text-decoration:underline;">[Editar]</a>';
                            } else {
                                var statusStr = ' | ';
                                langs.forEach(function (l) {
                                    var mark = l.integrated ? '<span style="color:green;"><i class="fa fa-check"></i></span>' : '<span style="color:red;"><i class="fa fa-times"></i></span>';
                                    statusStr += l.language + ' ' + mark + ' | ';
                                });
                                var defaultLang = langs.find(x => x.language === 'Default') || langs[0];
                                statusStr += '<a href="configemailtemplates.php?action=edit&id=' + defaultLang.id + '" target="_blank" style="text-decoration:underline;">[Editar]</a>';
                                span.innerHTML = statusStr;
                            }
                        }
                    });
                }
            });
        }

        // Analyze button event
        var analyzeBtn = form.querySelector('#paghiper-analyze-emails');
        if (analyzeBtn) {
            analyzeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                availableTemplates.forEach(function (tpl) {
                    var safeTpl = tpl.replace(/[^a-zA-Z0-9]/g, '');
                    var span = form.querySelector('#paghiper-tpl-status-' + safeTpl);
                    if (span) span.innerHTML = ' - <i>(Analisando...)</i>';
                });
                runAnalysis();
            });
        }

        // Auto-run on load
        runAnalysis();
    }

    function setupIntegrationUI(form) {
        // Sync auto_pdf_integration
        var nativeAutoPdf = form.querySelector('input[type="checkbox"][name="field[auto_pdf_integration]"]');
        var customAutoPdf = form.querySelector('#paghiper-custom-auto-pdf');
        if (nativeAutoPdf && customAutoPdf) {
            // Hide the native row
            var nativeRow = nativeAutoPdf.closest('tr');
            if (nativeRow) nativeRow.style.display = 'none';

            // Initial sync
            customAutoPdf.checked = nativeAutoPdf.checked;

            // Sync on change
            customAutoPdf.addEventListener('change', function () {
                nativeAutoPdf.checked = customAutoPdf.checked;
            });
        }

        var forceBtn = document.getElementById('paghiper-force-integration');
        if (forceBtn && !forceBtn.dataset.bound) {
            forceBtn.dataset.bound = "1";
            forceBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var tpl = document.getElementById('paghiper-template-selector').value;
                submitAjaxAction('force_integration', { template: tpl });
            });
        }

        var restoreBtn = document.getElementById('paghiper-restore-backup');
        if (restoreBtn && !restoreBtn.dataset.bound) {
            restoreBtn.dataset.bound = "1";
            restoreBtn.addEventListener('click', function (e) {
                e.preventDefault();
                var tpl = document.getElementById('paghiper-template-selector').value;
                var backup = document.getElementById('paghiper-backup-selector').value;
                if (!backup) {
                    alert("Selecione um backup para restaurar.");
                    return;
                }
                if (confirm("Tem certeza que deseja restaurar este backup? O arquivo atual será substituído.")) {
                    submitAjaxAction('restore_backup', { template: tpl, backup: backup });
                }
            });
        }

        var tplSelector = document.getElementById('paghiper-template-selector');
        if (tplSelector && !tplSelector.dataset.bound) {
            tplSelector.dataset.bound = "1";
            tplSelector.addEventListener('change', function () {
                submitAjaxAction('get_status', { template: this.value }, function (res) {
                    if (res.status) {
                        document.getElementById('paghiper-int-status').innerHTML = res.integrated ? '<span style="color:green;font-weight:bold;">Integrado</span>' : '<span style="color:red;font-weight:bold;">Não Integrado</span>';

                        var backupSel = document.getElementById('paghiper-backup-selector');
                        backupSel.innerHTML = '';
                        if (res.backups && res.backups.length > 0) {
                            res.backups.forEach(function (b) {
                                var opt = document.createElement('option');
                                opt.value = b.filename;
                                opt.innerHTML = b.filename + " (" + b.date + ")";
                                backupSel.appendChild(opt);
                            });
                            document.getElementById('paghiper-restore-backup').disabled = false;
                        } else {
                            var opt = document.createElement('option');
                            opt.value = "";
                            opt.innerHTML = "Nenhum backup disponível";
                            backupSel.appendChild(opt);
                            document.getElementById('paghiper-restore-backup').disabled = true;
                        }
                    }
                });
            });
        }
    }

    function submitAjaxAction(action, data, callback) {
        var formData = new FormData();
        formData.append('paghiper_action', action);
        for (var key in data) {
            formData.append(key, data[key]);
        }

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(res => {
                if (callback) {
                    callback(res);
                } else {
                    if (res.success) {
                        alert("Ação realizada com sucesso!");
                        window.location.reload();
                    } else {
                        alert("Erro: " + (res.error || "Desconhecido"));
                    }
                }
            })
            .catch(err => {
                console.error(err);
                alert("Erro na requisição AJAX.");
            });
    }
});
