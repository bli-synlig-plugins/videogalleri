# Videogalleri

WordPress-plugin for videogalleri med horisontal slider, frittstående videospiller og egne undersider per video. Støtte for YouTube, Vimeo og Kontainer. Videoer kan kobles til prosjekter, sider eller innlegg.

**Versjon:** 1.3.0  
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
- **Video**-felt: lim inn YouTube-, Vimeo- eller Kontainer-lenke, eller Kontainer sin iframe- eller video-kode. Varighet og thumbnail hentes automatisk for YouTube og Vimeo. For Kontainer setter du utvalgt bilde selv.
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
| `[videogalleri_spiller kilde="https://…"]` | Frittstående spiller. Videofiler får egen kontrollinje. |

## UX Builder

Elementet **Videogalleri** finnes under **Content** (krever Flatsome UX Builder). Valgfritt filter på **prosjekt-ID**.

Elementet **Videospiller** ligger samme sted. Lim inn en YouTube-, Vimeo- eller Kontainer-lenke, eller iframe-/video-kode. Avrunding og kontrollfarge settes på elementet. Kontrollinjen (avspilling, tid, spoling, lyd og fullskjerm) gjelder videofiler. En iframe bruker leverandørens egne kontroller, men rammen kan fortsatt avrundes.

## Lisens

Ingen lisens er angitt i repoet ennå.
