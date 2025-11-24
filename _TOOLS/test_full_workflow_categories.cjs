const { chromium } = require('playwright');

(async () => {
    console.log('=== FULL WORKFLOW TEST: EDIT → SAVE → REDIRECT → VERIFY ===\n');

    const browser = await chromium.launch({
        headless: false,
        slowMo: 1000
    });

    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });

    const page = await context.newPage();

    try {
        // STEP 1: Bezpośredni link do produktu 11034
        console.log('STEP 1: Wchodzę na https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        await page.waitForSelector('[wire\\:id]', { timeout: 15000 });
        await page.waitForTimeout(2000);
        console.log('✅ Produkt załadowany\n');

        await page.screenshot({ path: 'screenshots/workflow_01_loaded.png', fullPage: true });

        // STEP 2: Kliknij tab "B2B Test DEV"
        console.log('STEP 2: Klikam tab "B2B Test DEV"');
        const shopTab = page.locator('button:has-text("B2B Test DEV")').first();
        await shopTab.waitFor({ timeout: 5000 });
        await shopTab.click();
        console.log('✅ Kliknąłem tab\n');

        // STEP 3: Czekam na załadowanie kategorii (może być opóźnienie z PrestaShop)
        console.log('STEP 3: Czekam na załadowanie kategorii (3 sekundy + sprawdzam czy wire:loading zniknął)');
        await page.waitForTimeout(3000);

        // Czekaj aż wire:loading zniknie (jeśli istnieje)
        await page.waitForSelector('[wire\\:loading]', { state: 'hidden', timeout: 10000 }).catch(() => {
            console.log('  (brak wire:loading lub już zniknął)');
        });

        console.log('✅ Kategorie załadowane\n');

        // Przewiń do sekcji kategorii
        console.log('   Przewijam do sekcji kategorii...');
        await page.evaluate(() => {
            // Find element containing "Kategorie" text (standard DOM)
            const allElements = document.querySelectorAll('h3, label');
            let categorySection = null;

            for (const el of allElements) {
                if (el.textContent.includes('Kategorie')) {
                    categorySection = el;
                    break;
                }
            }

            // Fallback: find checkbox with wire:model
            if (!categorySection) {
                categorySection = document.querySelector('[wire\\:model*="shopCategories"]');
            }

            if (categorySection) {
                categorySection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        await page.waitForTimeout(1000);

        await page.screenshot({ path: 'screenshots/workflow_02_categories_visible.png', fullPage: true });

        // Sprawdź jakie kategorie są zaznaczone PRZED zmianą
        const categoriesBefore = await page.evaluate(() => {
            const selected = [];
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => {
                // Check both wire:model and x-model (Alpine.js)
                const wireModel = cb.getAttribute('wire:model') || '';
                const xModel = cb.getAttribute('x-model') || '';

                if ((wireModel.includes('shopCategories') || xModel.includes('isSelected')) && cb.checked) {
                    // Find label text from parent or next sibling
                    let text = '';
                    const parent = cb.parentElement;
                    if (parent) {
                        // Get all text content but clean up
                        text = parent.textContent.replace(/└─/g, '').trim();
                        // If too long (includes extra text), try to find just the category name
                        const labelElement = parent.querySelector('label');
                        if (labelElement) {
                            text = labelElement.textContent.replace(/└─/g, '').trim();
                        }
                    }
                    if (text && !text.includes('Ustaw główną')) {
                        selected.push(text);
                    }
                }
            });
            return selected;
        });

        console.log('   Kategorie PRZED zmianą:');
        categoriesBefore.forEach(cat => console.log(`     - ${cat}`));
        console.log('');

        // STEP 4: Zaznacz/odznacz jakieś kategorie
        console.log('STEP 4: Zaznaczam/odznaczam kategorie aby wywołać zmianę');

        // Znajdź pierwszą niezaznaczoną kategorię i ją zaznacz (lub odwrotnie)
        const toggleResult = await page.evaluate(() => {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            let toggled = null;

            for (const cb of checkboxes) {
                // Check both wire:model and x-model (Alpine.js)
                const wireModel = cb.getAttribute('wire:model') || '';
                const xModel = cb.getAttribute('x-model') || '';

                // Look for category-related models (shopCategories, isSelected, etc.)
                if (wireModel.includes('shopCategories') ||
                    xModel.includes('isSelected')) {

                    // Get clean category name
                    let text = '';
                    const parent = cb.parentElement;
                    if (parent) {
                        text = parent.textContent.replace(/└─/g, '').trim();
                        const labelElement = parent.querySelector('label');
                        if (labelElement) {
                            text = labelElement.textContent.replace(/└─/g, '').trim();
                        }
                    }

                    // Skip if text includes button text
                    if (text.includes('Ustaw główną')) continue;

                    // Toggle first category checkbox
                    const wasBefore = cb.checked;
                    cb.click();

                    toggled = {
                        name: text,
                        before: wasBefore,
                        after: cb.checked
                    };
                    break;
                }
            }

            return toggled;
        });

        if (toggleResult) {
            console.log(`   ✅ Zmieniłem: ${toggleResult.name}`);
            console.log(`      Przed: ${toggleResult.before ? 'zaznaczony' : 'odznaczony'}`);
            console.log(`      Po: ${toggleResult.after ? 'zaznaczony' : 'odznaczony'}`);
        } else {
            console.log('   ⚠️ Nie znalazłem kategorii do zmiany');
        }
        console.log('');

        await page.waitForTimeout(1000);
        await page.screenshot({ path: 'screenshots/workflow_03_changed.png', fullPage: true });

        // Sprawdź kategorie PO zmianie
        const categoriesAfterChange = await page.evaluate(() => {
            const selected = [];
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => {
                const wireModel = cb.getAttribute('wire:model') || '';
                const xModel = cb.getAttribute('x-model') || '';

                if ((wireModel.includes('shopCategories') || xModel.includes('isSelected')) && cb.checked) {
                    let text = '';
                    const parent = cb.parentElement;
                    if (parent) {
                        text = parent.textContent.replace(/└─/g, '').trim();
                        const labelElement = parent.querySelector('label');
                        if (labelElement) {
                            text = labelElement.textContent.replace(/└─/g, '').trim();
                        }
                    }
                    if (text && !text.includes('Ustaw główną')) {
                        selected.push(text);
                    }
                }
            });
            return selected;
        });

        console.log('   Kategorie PO zmianie:');
        categoriesAfterChange.forEach(cat => console.log(`     - ${cat}`));
        console.log('');

        // STEP 5: Kliknij "Zapisz zmiany"
        console.log('STEP 5: Klikam "Zapisz zmiany"');

        // Znajdź przycisk "Zapisz zmiany"
        const saveButton = page.locator('button:has-text("Zapisz zmiany")').first();
        await saveButton.waitFor({ timeout: 5000 });
        await saveButton.click();
        console.log('✅ Kliknąłem "Zapisz zmiany"\n');

        // STEP 6: Czekaj na redirect na listę produktów
        console.log('STEP 6: Czekam na redirect na https://ppm.mpptrade.pl/admin/products');

        await page.waitForURL('**/admin/products', { timeout: 15000 }).catch(async () => {
            console.log('   ⚠️ Redirect timeout - sprawdzam aktualny URL...');
            const currentUrl = page.url();
            console.log(`   Aktualny URL: ${currentUrl}`);

            if (!currentUrl.includes('/admin/products/11034')) {
                console.log('   ✅ Jesteśmy poza edycją produktu');
            } else {
                console.log('   ❌ Nadal jesteśmy na stronie edycji!');
                await page.screenshot({ path: 'screenshots/workflow_FAILED_no_redirect.png', fullPage: true });
                throw new Error('Redirect nie zadziałał - nadal na stronie edycji');
            }
        });

        const redirectUrl = page.url();
        console.log(`✅ Redirect zakończony: ${redirectUrl}\n`);

        await page.waitForTimeout(2000);
        await page.screenshot({ path: 'screenshots/workflow_04_product_list.png', fullPage: true });

        // STEP 7: Wejdź na produkt Q-KAYO-EA70 (bezpośredni link)
        console.log('STEP 7: Wchodzę na kartę produktu Q-KAYO-EA70 przez bezpośredni link');
        await page.goto('https://ppm.mpptrade.pl/admin/products/11034/edit');
        console.log('✅ Otworzyłem produkt Q-KAYO-EA70\n');

        await page.waitForSelector('[wire\\:id]', { timeout: 15000 });
        await page.waitForTimeout(2000);

        // STEP 8: Powtórz kroki 2-3 i zweryfikuj checkboxy
        console.log('STEP 8: Ponownie klikam tab "B2B Test DEV" i weryfikuję kategorie');

        const shopTab2 = page.locator('button:has-text("B2B Test DEV")').first();
        await shopTab2.waitFor({ timeout: 5000 });
        await shopTab2.click();
        console.log('✅ Kliknąłem tab\n');

        // Czekaj na załadowanie
        await page.waitForTimeout(3000);
        await page.waitForSelector('[wire\\:loading]', { state: 'hidden', timeout: 10000 }).catch(() => {});

        // Przewiń do kategorii
        await page.evaluate(() => {
            // Find element containing "Kategorie" text (standard DOM)
            const allElements = document.querySelectorAll('h3, label');
            let categorySection = null;

            for (const el of allElements) {
                if (el.textContent.includes('Kategorie')) {
                    categorySection = el;
                    break;
                }
            }

            // Fallback: find checkbox with wire:model
            if (!categorySection) {
                categorySection = document.querySelector('[wire\\:model*="shopCategories"]');
            }

            if (categorySection) {
                categorySection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
        await page.waitForTimeout(1000);

        await page.screenshot({ path: 'screenshots/workflow_05_verification.png', fullPage: true });

        // Sprawdź kategorie po reload
        const categoriesAfterReload = await page.evaluate(() => {
            const selected = [];
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            checkboxes.forEach(cb => {
                const wireModel = cb.getAttribute('wire:model') || '';
                const xModel = cb.getAttribute('x-model') || '';

                if ((wireModel.includes('shopCategories') || xModel.includes('isSelected')) && cb.checked) {
                    let text = '';
                    const parent = cb.parentElement;
                    if (parent) {
                        text = parent.textContent.replace(/└─/g, '').trim();
                        const labelElement = parent.querySelector('label');
                        if (labelElement) {
                            text = labelElement.textContent.replace(/└─/g, '').trim();
                        }
                    }
                    if (text && !text.includes('Ustaw główną')) {
                        selected.push(text);
                    }
                }
            });
            return selected;
        });

        console.log('=== VERIFICATION RESULTS ===\n');

        console.log('Kategorie PO zmianie (przed zapisem):');
        categoriesAfterChange.forEach(cat => console.log(`  - ${cat}`));
        console.log('');

        console.log('Kategorie PO reload (po zapisie i ponownym otwarciu):');
        categoriesAfterReload.forEach(cat => console.log(`  - ${cat}`));
        console.log('');

        // Porównaj
        const match = JSON.stringify(categoriesAfterChange.sort()) === JSON.stringify(categoriesAfterReload.sort());

        if (match) {
            console.log('✅✅✅ SUCCESS! ✅✅✅');
            console.log('✅ Kategorie się UTRZYMAŁY po zapisie i reload!');
            console.log('✅ Workflow działa poprawnie!');
        } else {
            console.log('❌ FAILED!');
            console.log('❌ Kategorie NIE UTRZYMAŁY SIĘ po zapisie!');
            console.log('');
            console.log('Expected (po zmianie):');
            categoriesAfterChange.forEach(cat => console.log(`  - ${cat}`));
            console.log('');
            console.log('Got (po reload):');
            categoriesAfterReload.forEach(cat => console.log(`  - ${cat}`));
        }

        console.log('\n=== TEST COMPLETE ===');

        // Trzymaj przeglądarkę otwartą przez 15 sekund
        console.log('\nBrowser will close in 15 seconds...');
        await page.waitForTimeout(15000);

    } catch (error) {
        console.error('\n❌ ERROR:', error.message);
        console.error(error.stack);

        await page.screenshot({ path: 'screenshots/workflow_ERROR.png', fullPage: true });
        console.log('📸 Error screenshot saved');

    } finally {
        await browser.close();
    }
})();
