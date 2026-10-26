# Videogalleri

WordPress-plugin for videogalleri med horisontal slider, egne undersider per video, og støtte for YouTube/Vimeo. Videoer kan kobles til prosjekter, sider eller innlegg.

**Versjon:** 1.1.1  
**Forfatter:** Benjamin Pedersen

## Krav

- WordPress
- Anbefalt: Flatsome (UX Builder-element og temastiler)

## Installasjon

1. Last ned release-zip fra [Releases](https://github.com/bli-synlig-plugins/videogalleri/releases), eller klon dette repoet.
2. Legg mappen `videogalleri` i `wp-content/plugins/`.
3. Aktiver **Videogalleri** under **Plugins**.
4. Etter aktivering: gå til **Innstillinger → Permalenker** og lagre én gang (rewrite for `/video/`-undersider).

## Admin

- **Videogalleri** i menyen: opprett og rediger videoer (tittel, miniatyrbilde, rekkefølge).
- **Video**-felt: lim inn YouTube- eller Vimeo-URL. Varighet og thumbnail hentes automatisk ved lagring.
- **Knytt til prosjekt**: koble video til side, innlegg eller annet innhold.
- **Rekkefølge**: egen side for å sortere videoer (drag-and-drop).
- **Innstillinger**: farger for avspillingsknapp, videoundersider og «Se prosjekt»-knapp.

Videoundersider bruker slug **`/video/{navn}/`**.

## Shortcodes

| Shortcode | Beskrivelse |
|-----------|-------------|
| `[videogalleri]` | Horisontal slider med publiserte videoer |
| `[videogalleri prosjekt_id="123"]` | Samme, filtrert på koblet innhold |
| `[videogalleri_video]` | Embed for video koblet til gjeldende side/innlegg |
| `[videogalleri_video video_id="456" tittel="ja" info="ja" lenke="ja"]` | Embed med valgfrie attributter |

## UX Builder

Elementet **Videogalleri** finnes under **Content** (krever Flatsome UX Builder). Valgfritt filter på **prosjekt-ID**.

## Lisens

Ingen lisens er angitt i repoet ennå.
