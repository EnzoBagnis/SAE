// Module de rendu des tentatives

export class AttemptsRenderer {
    renderAttempts(attempts) {
        const attemptsTitle = document.createElement('h3');
        attemptsTitle.textContent = `Historique des tentatives (${attempts.length})`;
        attemptsTitle.style.marginTop = '2rem';
        attemptsTitle.style.marginBottom = '1rem';
        attemptsTitle.style.color = '#2c3e50';

        const attemptsContainer = document.createElement('div');
        attemptsContainer.className = 'attempts-container';

        attempts.forEach((attempt, index) => {
            const attemptCard = this.createAttemptCard(attempt, index);
            attemptsContainer.appendChild(attemptCard);
        });

        // Ajouter le bouton "Retour en haut"
        this.addScrollToTopButton();

        return { title: attemptsTitle, container: attemptsContainer };
    }

    createAttemptCard(attempt, index) {
        const card = document.createElement('div');
        Object.assign(card.style, {
            background: 'white',
            border: '1px solid #e0e0e0',
            borderRadius: '8px',
            marginBottom: '1rem',
            boxShadow: '0 2px 4px rgba(0,0,0,0.05)',
            overflow: 'hidden'
        });

        const header = this.createAttemptHeader(attempt, index);
        card.appendChild(header);

        // Conteneur pour le contenu rétractable
        const contentWrapper = document.createElement('div');
        contentWrapper.className = 'attempt-content';
        contentWrapper.style.padding = '0 1.5rem 1.5rem 1.5rem';
        contentWrapper.style.display = 'block'; // Par défaut déplié

        const detailsTable = this.createDetailsTable(attempt);
        contentWrapper.appendChild(detailsTable);

        if (attempt.upload) {
            const codeSection = this.createCodeSection(attempt.upload);
            contentWrapper.appendChild(codeSection);
        }

        if (attempt.test_cases && Array.isArray(attempt.test_cases) && attempt.test_cases.length > 0) {
            const testCasesSection = this.createTestCasesSection(attempt);
            contentWrapper.appendChild(testCasesSection);
        }

        card.appendChild(contentWrapper);

        return card;
    }

    createAttemptHeader(attempt, index) {
        const header = document.createElement('div');
        Object.assign(header.style, {
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            padding: '1.5rem',
            borderBottom: '2px solid #f0f0f0',
            cursor: 'pointer',
            transition: 'background-color 0.2s'
        });

        // Hover effect
        header.addEventListener('mouseenter', () => {
            header.style.backgroundColor = '#f8f9fa';
        });
        header.addEventListener('mouseleave', () => {
            header.style.backgroundColor = 'transparent';
        });

        const leftSection = document.createElement('div');
        leftSection.style.display = 'flex';
        leftSection.style.alignItems = 'center';
        leftSection.style.gap = '1rem';

        // Icône de dépliage/rétractation
        const toggleIcon = document.createElement('span');
        toggleIcon.className = 'toggle-icon';
        toggleIcon.textContent = '▼';
        Object.assign(toggleIcon.style, {
            fontSize: '0.8rem',
            color: '#666',
            transition: 'transform 0.3s'
        });

        const attemptInfo = document.createElement('div');

        const attemptNumber = document.createElement('div');
        Object.assign(attemptNumber.style, {
            fontSize: '1.2rem',
            fontWeight: 'bold',
            color: '#2c3e50'
        });
        attemptNumber.textContent = `Tentative #${index + 1}`;

        // Afficher la date et l'exercice
        const metaInfo = document.createElement('div');
        Object.assign(metaInfo.style, {
            fontSize: '0.85rem',
            color: '#666',
            marginTop: '0.25rem'
        });

        const dateStr = attempt.submission_date ?
            new Date(attempt.submission_date).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            }) : 'Date inconnue';

        const exerciseName = attempt.funcname || attempt.exo_name || 'Exercice inconnu';
        metaInfo.innerHTML = `📅 ${dateStr} | 📝 <strong>${exerciseName}</strong>`;

        attemptInfo.appendChild(attemptNumber);
        attemptInfo.appendChild(metaInfo);

        leftSection.appendChild(toggleIcon);
        leftSection.appendChild(attemptInfo);

        const badge = this.createStatusBadge(attempt.correct);

        header.appendChild(leftSection);
        header.appendChild(badge);

        // Ajouter l'événement de clic pour rétracter/déplier
        header.addEventListener('click', () => {
            const content = header.nextElementSibling;
            const isVisible = content.style.display !== 'none';

            content.style.display = isVisible ? 'none' : 'block';
            toggleIcon.style.transform = isVisible ? 'rotate(-90deg)' : 'rotate(0deg)';
        });

        return header;
    }

    createStatusBadge(correct) {
        const badge = document.createElement('span');
        const isCorrect = correct === 1 || correct === '1';

        Object.assign(badge.style, {
            padding: '0.5rem 1rem',
            borderRadius: '20px',
            fontSize: '0.85rem',
            fontWeight: 'bold',
            background: isCorrect ? '#d4edda' : '#f8d7da',
            color: isCorrect ? '#155724' : '#721c24'
        });
        badge.textContent = isCorrect ? '✓ Réussi' : '✗ Échoué';

        return badge;
    }

    createDetailsTable(attempt) {
        const table = document.createElement('table');
        table.style.width = '100%';
        table.style.borderCollapse = 'collapse';
        table.style.marginTop = '1rem';

        const details = [
            { label: 'Extension', value: attempt.extension || 'N/A' },
            { label: 'Eval Set', value: attempt.eval_set || 'N/A' },
            { label: 'Ressource', value: attempt.resource_name || 'N/A' }
        ];

        details.forEach(detail => {
            const row = document.createElement('tr');

            const labelCell = document.createElement('td');
            Object.assign(labelCell.style, {
                padding: '0.5rem',
                fontWeight: 'bold',
                color: '#555',
                width: '150px'
            });
            labelCell.textContent = detail.label + ' :';

            const valueCell = document.createElement('td');
            Object.assign(valueCell.style, {
                padding: '0.5rem',
                color: '#333',
                wordBreak: 'break-word',
                overflowWrap: 'break-word'
            });
            valueCell.textContent = typeof detail.value === 'string' ? detail.value : String(detail.value);

            row.appendChild(labelCell);
            row.appendChild(valueCell);
            table.appendChild(row);
        });

        return table;
    }

    createCodeSection(upload) {
        const container = document.createElement('div');

        const title = document.createElement('div');
        Object.assign(title.style, {
            fontWeight: 'bold',
            marginTop: '1rem',
            marginBottom: '0.5rem',
            color: '#2c3e50'
        });
        title.textContent = 'Code soumis :';

        const codeBlock = document.createElement('pre');
        Object.assign(codeBlock.style, {
            background: '#f4f4f4',
            padding: '1rem',
            borderRadius: '4px',
            overflow: 'auto',
            fontSize: '0.85rem',
            maxHeight: '300px',
            whiteSpace: 'pre-wrap',
            wordBreak: 'break-word',
            border: '1px solid #ddd'
        });

        let uploadContent = upload;
        if (typeof uploadContent === 'object') {
            uploadContent = JSON.stringify(uploadContent, null, 2);
        }
        codeBlock.textContent = uploadContent;

        container.appendChild(title);
        container.appendChild(codeBlock);

        return container;
    }

    addScrollToTopButton() {
        // Vérifier si le bouton existe déjà
        if (document.getElementById('scrollToTopBtn')) {
            return;
        }

        const scrollBtn = document.createElement('button');
        scrollBtn.id = 'scrollToTopBtn';
        scrollBtn.innerHTML = '⬆';
        scrollBtn.title = 'Retour en haut';

        Object.assign(scrollBtn.style, {
            position: 'fixed',
            bottom: '30px',
            right: '30px',
            width: '55px',
            height: '55px',
            borderRadius: '12px',
            backgroundColor: '#2c3e50',
            color: 'white',
            border: '2px solid rgba(255, 255, 255, 0.2)',
            fontSize: '20px',
            cursor: 'pointer',
            boxShadow: '0 6px 20px rgba(44, 62, 80, 0.3)',
            display: 'none',
            zIndex: '1000',
            transition: 'all 0.3s ease',
            fontWeight: 'bold',
            opacity: '0.9'
        });

        // Effet hover
        scrollBtn.addEventListener('mouseenter', () => {
            scrollBtn.style.backgroundColor = '#34495e';
            scrollBtn.style.transform = 'translateY(-3px)';
            scrollBtn.style.boxShadow = '0 8px 25px rgba(44, 62, 80, 0.4)';
            scrollBtn.style.opacity = '1';
        });
        scrollBtn.addEventListener('mouseleave', () => {
            scrollBtn.style.backgroundColor = '#2c3e50';
            scrollBtn.style.transform = 'translateY(0)';
            scrollBtn.style.boxShadow = '0 6px 20px rgba(44, 62, 80, 0.3)';
            scrollBtn.style.opacity = '0.9';
        });

        // Clic pour remonter
        scrollBtn.addEventListener('click', () => {
            const mainContent = document.querySelector('.main-content') || window;
            if (mainContent === window) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                mainContent.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });

        document.body.appendChild(scrollBtn);

        // Afficher/cacher le bouton selon le scroll avec animation
        const toggleScrollButton = () => {
            const mainContent = document.querySelector('.main-content');
            const scrollTop = mainContent ? mainContent.scrollTop : window.pageYOffset;

            if (scrollTop > 300) {
                scrollBtn.style.display = 'block';
                // Animation d'apparition
                setTimeout(() => {
                    scrollBtn.style.opacity = '0.9';
                    scrollBtn.style.transform = 'translateY(0)';
                }, 10);
            } else {
                scrollBtn.style.opacity = '0';
                scrollBtn.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    if (scrollTop <= 300) {
                        scrollBtn.style.display = 'none';
                    }
                }, 300);
            }
        };

        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.addEventListener('scroll', toggleScrollButton);
        } else {
            window.addEventListener('scroll', toggleScrollButton);
        }
    }

    createTestCasesSection(attempt) {
        const container = document.createElement('div');

        const title = document.createElement('div');
        Object.assign(title.style, {
            fontWeight: 'bold',
            marginTop: '1.5rem',
            marginBottom: '0.75rem',
            color: '#2c3e50',
            fontSize: '1.1rem'
        });
        title.textContent = '📋 Test Cases de l\'exercice';

        const testCasesContainer = document.createElement('div');
        testCasesContainer.style.display = 'grid';
        testCasesContainer.style.gap = '0.75rem';
        testCasesContainer.style.marginTop = '0.5rem';

        const allPassed = attempt.correct === 1 || attempt.correct === '1';
        let individualResults = [];

        if (attempt.aes1 !== undefined) individualResults.push(attempt.aes1 === '1' || attempt.aes1 === 1);
        if (attempt.aes2 !== undefined) individualResults.push(attempt.aes2 === '1' || attempt.aes2 === 1);
        if (attempt.aes3 !== undefined) individualResults.push(attempt.aes3 === '1' || attempt.aes3 === 1);

        let passedCount = 0;

        attempt.test_cases.forEach((testCase, tcIndex) => {
            let testPassed = allPassed;
            if (!allPassed && individualResults.length > tcIndex) {
                testPassed = individualResults[tcIndex];
            }

            if (testPassed) passedCount++;

            const testCaseCard = this.createTestCaseCard(testCase, tcIndex, testPassed, attempt.funcname);
            testCasesContainer.appendChild(testCaseCard);
        });

        const summary = this.createTestCaseSummary(passedCount, attempt.test_cases.length);

        container.appendChild(title);
        container.appendChild(testCasesContainer);
        container.appendChild(summary);

        return container;
    }

    createTestCaseCard(testCase, index, passed, funcname) {
        const card = document.createElement('div');
        Object.assign(card.style, {
            background: passed ? '#f0f9ff' : '#fff5f5',
            border: passed ? '2px solid #bfdbfe' : '2px solid #fecaca',
            borderRadius: '8px',
            padding: '1rem',
            transition: 'all 0.2s'
        });

        const header = document.createElement('div');
        Object.assign(header.style, {
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
            marginBottom: '0.75rem'
        });

        const testNumber = document.createElement('span');
        Object.assign(testNumber.style, {
            fontWeight: 'bold',
            color: '#1f2937',
            fontSize: '0.95rem'
        });
        testNumber.textContent = `Test Case #${index + 1}`;

        const statusBadge = document.createElement('span');
        Object.assign(statusBadge.style, {
            padding: '0.3rem 0.75rem',
            borderRadius: '12px',
            fontSize: '0.8rem',
            fontWeight: 'bold',
            background: passed ? '#10b981' : '#ef4444',
            color: 'white'
        });
        statusBadge.textContent = passed ? '✓ Réussi' : '✗ Échoué';

        header.appendChild(testNumber);
        header.appendChild(statusBadge);
        card.appendChild(header);

        const inputLabel = document.createElement('div');
        Object.assign(inputLabel.style, {
            fontWeight: '600',
            color: '#4b5563',
            fontSize: '0.85rem',
            marginBottom: '0.4rem'
        });
        inputLabel.textContent = 'Entrée(s) :';
        card.appendChild(inputLabel);

        const inputValue = document.createElement('div');
        Object.assign(inputValue.style, {
            background: '#f9fafb',
            padding: '0.6rem',
            borderRadius: '4px',
            fontFamily: 'monospace',
            fontSize: '0.85rem',
            color: '#1f2937',
            overflowX: 'auto',
            whiteSpace: 'pre-wrap',
            wordBreak: 'break-word'
        });

        inputValue.textContent = this.formatTestCaseInput(testCase, funcname);
        card.appendChild(inputValue);

        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-2px)';
            card.style.boxShadow = '0 4px 12px rgba(0,0,0,0.1)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = 'none';
        });

        return card;
    }

    formatTestCaseInput(testCase, funcname) {
        if (typeof testCase === 'object' && testCase !== null) {
            if (testCase.__tuple__ && Array.isArray(testCase.items)) {
                if (funcname) {
                    return `${funcname}(${testCase.items.map(item => JSON.stringify(item)).join(', ')})`;
                } else {
                    return `Arguments: ${testCase.items.map(item => JSON.stringify(item)).join(', ')}`;
                }
            } else {
                return JSON.stringify(testCase, null, 2);
            }
        } else {
            if (funcname) {
                return `${funcname}(${JSON.stringify(testCase)})`;
            } else {
                return JSON.stringify(testCase);
            }
        }
    }

    createTestCaseSummary(passedCount, totalCount) {
        const summary = document.createElement('div');
        const allPassed = passedCount === totalCount;

        Object.assign(summary.style, {
            marginTop: '1rem',
            padding: '0.75rem 1rem',
            background: allPassed ? '#ecfdf5' : '#fef3c7',
            border: allPassed ? '2px solid #a7f3d0' : '2px solid #fde68a',
            borderRadius: '8px',
            fontSize: '0.95rem',
            fontWeight: '600',
            color: allPassed ? '#065f46' : '#92400e',
            textAlign: 'center'
        });

        const icon = allPassed ? '✅' : '⚠️';
        summary.textContent = `${icon} Résultat : ${passedCount}/${totalCount} test case(s) réussi(s)`;

        return summary;
    }
}
