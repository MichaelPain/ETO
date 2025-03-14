/**
 * Classe per la visualizzazione dei bracket dei tornei
 * 
 * Implementa un renderer moderno e responsive per i bracket dei tornei
 * 
 * @package ETO
 * @since 2.5.1
 */

class ETOBracketRenderer {
    /**
     * Costruttore
     * 
     * @param {string} containerId ID del container HTML
     * @param {Object} tournamentData Dati del torneo
     * @param {Object} options Opzioni di configurazione
     */
    constructor(containerId, tournamentData, options = {}) {
        // Elementi DOM
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('Container not found:', containerId);
            return;
        }
        
        // Dati del torneo
        this.tournamentData = tournamentData;
        
        // Opzioni di configurazione
        this.options = Object.assign({
            lineColor: '#ccc',
            matchBgColor: '#f8f8f8',
            matchBorderColor: '#ddd',
            winnerBgColor: '#e8f5e9',
            loserBgColor: '#ffebee',
            fontFamily: 'Arial, sans-serif',
            matchWidth: 200,
            matchHeight: 80,
            horizontalGap: 60,
            verticalGap: 30,
            lineWidth: 2,
            roundTitles: true,
            responsive: true,
            animation: true
        }, options);
        
        // Stato interno
        this.rounds = [];
        this.matches = [];
        this.svgElement = null;
        this.isInitialized = false;
        
        // Inizializza il renderer
        this.init();
    }
    
    /**
     * Inizializza il renderer
     */
    init() {
        if (!this.tournamentData || !this.tournamentData.format) {
            console.error('Invalid tournament data');
            return;
        }
        
        // Organizza i dati in base al formato del torneo
        switch (this.tournamentData.format) {
            case 'single':
                this.prepareSingleEliminationData();
                break;
            case 'double':
                this.prepareDoubleEliminationData();
                break;
            case 'swiss':
                this.prepareSwissData();
                break;
            default:
                console.error('Unsupported tournament format:', this.tournamentData.format);
                return;
        }
        
        // Crea l'elemento SVG
        this.createSvgElement();
        
        // Renderizza il bracket
        this.render();
        
        // Aggiungi listener per il resize
        if (this.options.responsive) {
            window.addEventListener('resize', this.handleResize.bind(this));
        }
        
        this.isInitialized = true;
    }
    
    /**
     * Prepara i dati per un torneo a eliminazione singola
     */
    prepareSingleEliminationData() {
        const matches = this.tournamentData.matches || [];
        
        // Raggruppa i match per round
        const roundsMap = {};
        
        matches.forEach(match => {
            if (!roundsMap[match.round]) {
                roundsMap[match.round] = [];
            }
            roundsMap[match.round].push(match);
        });
        
        // Converti la mappa in array ordinato
        this.rounds = Object.keys(roundsMap)
            .sort((a, b) => parseInt(a) - parseInt(b))
            .map(round => {
                return {
                    round: parseInt(round),
                    title: this.getRoundTitle(parseInt(round), this.tournamentData.format),
                    matches: roundsMap[round].sort((a, b) => a.match_number - b.match_number)
                };
            });
        
        // Calcola il numero totale di match
        this.matches = matches;
    }
    
    /**
     * Prepara i dati per un torneo a doppia eliminazione
     */
    prepareDoubleEliminationData() {
        const matches = this.tournamentData.matches || [];
        
        // Raggruppa i match per round e bracket (winners/losers)
        const winnersRounds = {};
        const losersRounds = {};
        let finalRound = null;
        
        matches.forEach(match => {
            if (match.bracket === 'winners') {
                if (!winnersRounds[match.round]) {
                    winnersRounds[match.round] = [];
                }
                winnersRounds[match.round].push(match);
            } else if (match.bracket === 'losers') {
                if (!losersRounds[match.round]) {
                    losersRounds[match.round] = [];
                }
                losersRounds[match.round].push(match);
            } else if (match.bracket === 'final') {
                finalRound = match;
            }
        });
        
        // Converti le mappe in array ordinati
        const winners = Object.keys(winnersRounds)
            .sort((a, b) => parseInt(a) - parseInt(b))
            .map(round => {
                return {
                    round: parseInt(round),
                    title: this.getRoundTitle(parseInt(round), 'winners'),
                    matches: winnersRounds[round].sort((a, b) => a.match_number - b.match_number),
                    bracket: 'winners'
                };
            });
        
        const losers = Object.keys(losersRounds)
            .sort((a, b) => parseInt(a) - parseInt(b))
            .map(round => {
                return {
                    round: parseInt(round),
                    title: this.getRoundTitle(parseInt(round), 'losers'),
                    matches: losersRounds[round].sort((a, b) => a.match_number - b.match_number),
                    bracket: 'losers'
                };
            });
        
        // Aggiungi il round finale se presente
        if (finalRound) {
            winners.push({
                round: winners.length + 1,
                title: this.getRoundTitle(winners.length + 1, 'final'),
                matches: [finalRound],
                bracket: 'final'
            });
        }
        
        // Combina i round
        this.rounds = [...winners, ...losers];
        
        // Calcola il numero totale di match
        this.matches = matches;
    }
    
    /**
     * Prepara i dati per un torneo Swiss
     */
    prepareSwissData() {
        const matches = this.tournamentData.matches || [];
        
        // Raggruppa i match per round
        const roundsMap = {};
        
        matches.forEach(match => {
            if (!roundsMap[match.round]) {
                roundsMap[match.round] = [];
            }
            roundsMap[match.round].push(match);
        });
        
        // Converti la mappa in array ordinato
        this.rounds = Object.keys(roundsMap)
            .sort((a, b) => parseInt(a) - parseInt(b))
            .map(round => {
                return {
                    round: parseInt(round),
                    title: `Round ${round}`,
                    matches: roundsMap[round].sort((a, b) => a.match_number - b.match_number)
                };
            });
        
        // Calcola il numero totale di match
        this.matches = matches;
    }
    
    /**
     * Crea l'elemento SVG
     */
    createSvgElement() {
        // Rimuovi eventuali elementi SVG esistenti
        const existingSvg = this.container.querySelector('svg');
        if (existingSvg) {
            existingSvg.remove();
        }
        
        // Crea il nuovo elemento SVG
        this.svgElement = document.createElementNS('http://www.w3.org/2000/svg', 'svg') ;
        this.svgElement.setAttribute('class', 'eto-bracket');
        this.container.appendChild(this.svgElement);
        
        // Aggiungi lo stile di base
        const style = document.createElementNS('http://www.w3.org/2000/svg', 'style') ;
        style.textContent = `
            .eto-bracket-match {
                cursor: pointer;
                transition: transform 0.2s ease;
            }
            .eto-bracket-match:hover {
                transform: scale(1.05);
            }
            .eto-bracket-team {
                font-family: ${this.options.fontFamily};
                font-size: 12px;
                dominant-baseline: middle;
            }
            .eto-bracket-score {
                font-family: ${this.options.fontFamily};
                font-size: 14px;
                font-weight: bold;
                dominant-baseline: middle;
                text-anchor: middle;
            }
            .eto-bracket-round-title {
                font-family: ${this.options.fontFamily};
                font-size: 14px;
                font-weight: bold;
                text-anchor: middle;
            }
            .eto-bracket-line {
                stroke: ${this.options.lineColor};
                stroke-width: ${this.options.lineWidth};
                fill: none;
            }
            .eto-bracket-winner {
                fill: ${this.options.winnerBgColor};
            }
            .eto-bracket-loser {
                fill: ${this.options.loserBgColor};
            }
        `;
        this.svgElement.appendChild(style);
    }
    
    /**
     * Renderizza il bracket
     */
    render() {
        if (!this.svgElement || this.rounds.length === 0) {
            return;
        }
        
        // Calcola le dimensioni del bracket
        const dimensions = this.calculateDimensions();
        
        // Imposta le dimensioni dell'SVG
        this.svgElement.setAttribute('width', dimensions.width);
        this.svgElement.setAttribute('height', dimensions.height);
        this.svgElement.setAttribute('viewBox', `0 0 ${dimensions.width} ${dimensions.height}`);
        
        // Aggiungi un gruppo per i titoli dei round
        if (this.options.roundTitles) {
            this.renderRoundTitles(dimensions);
        }
        
        // Aggiungi un gruppo per i match
        this.renderMatches(dimensions);
        
        // Aggiungi un gruppo per le linee di connessione
        this.renderConnectors(dimensions);
    }
    
    /**
     * Calcola le dimensioni del bracket
     * 
     * @return {Object} Dimensioni del bracket
     */
    calculateDimensions() {
        const { matchWidth, matchHeight, horizontalGap, verticalGap } = this.options;
        
        // Calcola il numero massimo di match per round
        const maxMatchesPerRound = Math.max(...this.rounds.map(round => round.matches.length));
        
        // Calcola la larghezza totale
        const width = (this.rounds.length * matchWidth) + ((this.rounds.length - 1) * horizontalGap);
        
        // Calcola l'altezza totale
        const height = (maxMatchesPerRound * matchHeight) + ((maxMatchesPerRound - 1) * verticalGap) + 40; // 40px extra per i titoli
        
        return { width, height, maxMatchesPerRound };
    }
    
    /**
     * Renderizza i titoli dei round
     * 
     * @param {Object} dimensions Dimensioni del bracket
     */
    renderRoundTitles(dimensions) {
        const { matchWidth, horizontalGap } = this.options;
        
        this.rounds.forEach((round, roundIndex) => {
            const x = (roundIndex * matchWidth) + (roundIndex * horizontalGap) + (matchWidth / 2);
            const y = 20; // 20px dall'alto
            
            const title = document.createElementNS('http://www.w3.org/2000/svg', 'text') ;
            title.setAttribute('x', x);
            title.setAttribute('y', y);
            title.setAttribute('class', 'eto-bracket-round-title');
            title.textContent = round.title;
            
            this.svgElement.appendChild(title);
        });
    }
    
    /**
     * Renderizza i match
     * 
     * @param {Object} dimensions Dimensioni del bracket
     */
    renderMatches(dimensions) {
        const { matchWidth, matchHeight, horizontalGap, verticalGap } = this.options;
        
        this.rounds.forEach((round, roundIndex) => {
            const matchesInRound = round.matches.length;
            const totalMatchHeight = matchesInRound * matchHeight + (matchesInRound - 1) * verticalGap;
            const startY = (dimensions.height - totalMatchHeight) / 2;
            
            round.matches.forEach((match, matchIndex) => {
                const x = (roundIndex * matchWidth) + (roundIndex * horizontalGap);
                const y = startY + (matchIndex * matchHeight) + (matchIndex * verticalGap);
                
                // Crea un gruppo per il match
                const matchGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g') ;
                matchGroup.setAttribute('class', 'eto-bracket-match');
                matchGroup.setAttribute('data-match-id', match.id);
                matchGroup.setAttribute('transform', `translate(${x}, ${y})`);
                
                // Aggiungi il rettangolo di sfondo
                const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect') ;
                rect.setAttribute('width', matchWidth);
                rect.setAttribute('height', matchHeight);
                rect.setAttribute('rx', 4);
                rect.setAttribute('ry', 4);
                rect.setAttribute('fill', this.options.matchBgColor);
                rect.setAttribute('stroke', this.options.matchBorderColor);
                rect.setAttribute('stroke-width', 1);
                matchGroup.appendChild(rect);
                
                // Aggiungi una linea di separazione
                const separator = document.createElementNS('http://www.w3.org/2000/svg', 'line') ;
                separator.setAttribute('x1', 0);
                separator.setAttribute('y1', matchHeight / 2);
                separator.setAttribute('x2', matchWidth);
                separator.setAttribute('y2', matchHeight / 2);
                separator.setAttribute('stroke', this.options.matchBorderColor);
                separator.setAttribute('stroke-width', 1);
                matchGroup.appendChild(separator);
                
                // Aggiungi il team 1
                this.renderTeam(matchGroup, match.team1_name || 'TBD', match.team1_score || 0, 10, matchHeight / 4, matchWidth - 40);
                
                // Aggiungi il team 2
                this.renderTeam(matchGroup, match.team2_name || 'TBD', match.team2_score || 0, 10, (matchHeight * 3) / 4, matchWidth - 40);
                
                // Evidenzia il vincitore
                if (match.status === 'completed') {
                    if (match.team1_score > match.team2_score) {
                        const winner = document.createElementNS('http://www.w3.org/2000/svg', 'rect') ;
                        winner.setAttribute('x', 0);
                        winner.setAttribute('y', 0);
                        winner.setAttribute('width', matchWidth);
                        winner.setAttribute('height', matchHeight / 2);
                        winner.setAttribute('fill', this.options.winnerBgColor);
                        winner.setAttribute('opacity', 0.5);
                        matchGroup.insertBefore(winner, matchGroup.firstChild);
                    } else if (match.team2_score > match.team1_score) {
                        const winner = document.createElementNS('http://www.w3.org/2000/svg', 'rect') ;
                        winner.setAttribute('x', 0);
                        winner.setAttribute('y', matchHeight / 2);
                        winner.setAttribute('width', matchWidth);
                        winner.setAttribute('height', matchHeight / 2);
                        winner.setAttribute('fill', this.options.winnerBgColor);
                        winner.setAttribute('opacity', 0.5);
                        matchGroup.insertBefore(winner, matchGroup.firstChild);
                    }
                }
                
                // Aggiungi l'evento click
                matchGroup.addEventListener('click', () => this.handleMatchClick(match));
                
                // Aggiungi il match al SVG
                this.svgElement.appendChild(matchGroup);
                
                // Salva le coordinate del match per i connettori
                match.coordinates = {
                    x,
                    y,
                    width: matchWidth,
                    height: matchHeight
                };
            });
        });
    }
    
    /**
     * Renderizza un team
     * 
     * @param {SVGElement} parent Elemento padre
     * @param {string} name Nome del team
     * @param {number} score Punteggio del team
     * @param {number} x Coordinata X
     * @param {number} y Coordinata Y
     * @param {number} width Larghezza massima
     */
    renderTeam(parent, name, score, x, y, width) {
        // Aggiungi il nome del team
        const teamName = document.createElementNS('http://www.w3.org/2000/svg', 'text') ;
        teamName.setAttribute('x', x);
        teamName.setAttribute('y', y);
        teamName.setAttribute('class', 'eto-bracket-team');
        
        // Tronca il nome se troppo lungo
        let displayName = name;
        if (name.length > 20) {
            displayName = name.substring(0, 17) + '...';
        }
        teamName.textContent = displayName;
        parent.appendChild(teamName);
        
        // Aggiungi il punteggio
        const teamScore = document.createElementNS('http://www.w3.org/2000/svg', 'text') ;
        teamScore.setAttribute('x', width);
        teamScore.setAttribute('y', y);
        teamScore.setAttribute('class', 'eto-bracket-score');
        teamScore.textContent = score;
        parent.appendChild(teamScore);
    }
    
    /**
     * Renderizza i connettori tra i match
     * 
     * @param {Object} dimensions Dimensioni del bracket
     */
    renderConnectors(dimensions) {
        // Crea un gruppo per i connettori
        const connectorsGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g') ;
        connectorsGroup.setAttribute('class', 'eto-bracket-connectors');
        
        // Per ogni round tranne il primo
        for (let roundIndex = 1; roundIndex < this.rounds.length; roundIndex++) {
            const currentRound = this.rounds[roundIndex];
            const previousRound = this.rounds[roundIndex - 1];
            
            // Per ogni match nel round corrente
            currentRound.matches.forEach(match => {
                // Trova i match precedenti che portano a questo match
                const sourceMatches = this.findSourceMatches(match, previousRound.matches);
                
                // Se ci sono match precedenti, crea i connettori
                sourceMatches.forEach(sourceMatch => {
                    if (sourceMatch && sourceMatch.coordinates && match.coordinates) {
                        this.createConnector(connectorsGroup, sourceMatch.coordinates, match.coordinates);
                    }
                });
            });
        }
        
        // Aggiungi il gruppo al SVG
        this.svgElement.appendChild(connectorsGroup);
    }
    
    /**
     * Trova i match sorgente per un match
     * 
     * @param {Object} targetMatch Match di destinazione
     * @param {Array} sourceMatches Match sorgente potenziali
     * @return {Array} Match sorgente
     */
    findSourceMatches(targetMatch, sourceMatches) {
        // Per i tornei a eliminazione singola, usa la logica standard
        if (this.tournamentData.format === 'single') {
            const matchNumber = targetMatch.match_number;
            return sourceMatches.filter(m => {
                return Math.floor(m.match_number / 2) === Math.floor((matchNumber - 1) / 2);
            });
        }
        
        // Per i tornei a doppia eliminazione o Swiss, usa le informazioni esplicite
        if (targetMatch.source_matches && Array.isArray(targetMatch.source_matches)) {
            return targetMatch.source_matches.map(sourceId => {
                return sourceMatches.find(m => m.id === sourceId);
            }).filter(Boolean);
        }
        
        return [];
    }
    
    /**
     * Crea un connettore tra due match
     * 
     * @param {SVGElement} parent Elemento padre
     * @param {Object} source Coordinate del match sorgente
     * @param {Object} target Coordinate del match di destinazione
     */
    createConnector(parent, source, target) {
        const sourceX = source.x + source.width;
        const sourceY = source.y + (source.height / 2);
        const targetX = target.x;
        const targetY = target.y + (target.height / 2);
        const midX = sourceX + ((targetX - sourceX) / 2);
        
        // Crea il path
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path') ;
        path.setAttribute('class', 'eto-bracket-line');
        path.setAttribute('d', `M ${sourceX} ${sourceY} H ${midX} V ${targetY} H ${targetX}`);
        
        // Aggiungi animazione se abilitata
        if (this.options.animation) {
            const length = path.getTotalLength();
            path.setAttribute('stroke-dasharray', length);
            path.setAttribute('stroke-dashoffset', length);
            path.setAttribute('style', 'animation: eto-bracket-line-animation 1s ease forwards;');
            
            // Aggiungi l'animazione CSS se non esiste già
            if (!document.getElementById('eto-bracket-animations')) {
                const style = document.createElementNS('http://www.w3.org/2000/svg', 'style') ;
                style.setAttribute('id', 'eto-bracket-animations');
                style.textContent = `
                    @keyframes eto-bracket-line-animation {
                        to {
                            stroke-dashoffset: 0;
                        }
                    }
                `;
                this.svgElement.appendChild(style);
            }
        }
        
        parent.appendChild(path);
    }
    
    /**
     * Gestisce il click su un match
     * 
     * @param {Object} match Match cliccato
     */
    handleMatchClick(match) {
        // Emetti un evento personalizzato
        const event = new CustomEvent('eto-match-click', {
            detail: {
                match: match
            }
        });
        this.container.dispatchEvent(event);
        
        // Se è definita una callback, chiamala
        if (typeof this.options.onMatchClick === 'function') {
            this.options.onMatchClick(match);
        }
    }
    
    /**
     * Gestisce il ridimensionamento della finestra
     */
    handleResize() {
        if (!this.isInitialized) {
            return;
        }
        
        // Aggiorna il rendering
        this.render();
    }
    
    /**
     * Ottiene il titolo di un round
     * 
     * @param {number} round Numero del round
     * @param {string} format Formato del torneo
     * @return {string} Titolo del round
     */
    getRoundTitle(round, format) {
        // Per i tornei a eliminazione singola
        if (format === 'single') {
            const totalRounds = Math.log2(this.tournamentData.team_count || 0);
            
            if (round === totalRounds) {
                return 'Finale';
            } else if (round === totalRounds - 1) {
                return 'Semifinali';
            } else if (round === totalRounds - 2) {
                return 'Quarti';
            } else {
                return `Round ${round}`;
            }
        }
        
        // Per i bracket winners/losers
        if (format === 'winners') {
            return `Winners R${round}`;
        } else if (format === 'losers') {
            return `Losers R${round}`;
        } else if (format === 'final') {
            return 'Finale';
        }
        
        // Default
        return `Round ${round}`;
    }
    
    /**
     * Aggiorna i dati del torneo
     * 
     * @param {Object} tournamentData Nuovi dati del torneo
     */
    updateData(tournamentData) {
        this.tournamentData = tournamentData;
        
        // Reinizializza il renderer
        this.rounds = [];
        this.matches = [];
        this.init();
    }
}

// Inizializza i bracket quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
    // Cerca tutti i container di bracket
    const bracketContainers = document.querySelectorAll('.eto-bracket-container');
    
    bracketContainers.forEach(container => {
        // Ottieni i dati del torneo
        const tournamentDataStr = container.getAttribute('data-tournament');
        if (!tournamentDataStr) {
            return;
        }
        
        try {
            const tournamentData = JSON.parse(tournamentDataStr);
            
            // Crea il renderer
            const renderer = new ETOBracketRenderer(container.id, tournamentData, {
                onMatchClick: function(match) {
                    // Reindirizza alla pagina del match se definita
                    if (etoData && etoData.matchUrl) {
                        window.location.href = etoData.matchUrl.replace('{id}', match.id);
                    }
                }
            });
        } catch (e) {
            console.error('Error parsing tournament data:', e);
        }
    });
});
