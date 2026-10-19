

export default function switchNav() {
    const nav = document.getElementById('nav');
    const switchNav = document.getElementById('switch-nav');

    switchNav.addEventListener('click', () => {
        nav.classList.toggle('closed');
        console.log('clicou')
    })
}