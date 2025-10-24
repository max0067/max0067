// Test simple des fonctions themes
console.log('🔍 Test script chargé');

// Test 1: Vérifier localStorage
console.log('localStorage disponible:', typeof(Storage) !== "undefined");

// Test 2: Créer une fonction de test globale
window.TEST_CREATE_THEME = function() {
    console.log('TEST_CREATE_THEME appelée !');
    alert('La fonction TEST_CREATE_THEME fonctionne !');

    const theme = {
        id: 'test_' + Date.now(),
        name: 'Test',
        color: '#3B82F6',
        icon: '📁',
        count: 0
    };

    localStorage.setItem('test_theme', JSON.stringify(theme));
    console.log('Thème test sauvegardé:', theme);
    alert('Thème test créé : ' + JSON.stringify(theme, null, 2));
};

// Test 3: Vérifier si createNewTheme existe
setTimeout(() => {
    console.log('createNewTheme existe:', typeof window.createNewTheme);
    console.log('manageThemes existe:', typeof window.manageThemes);
    console.log('openTheme existe:', typeof window.openTheme);
}, 2000);

console.log('✅ Script de test terminé');
