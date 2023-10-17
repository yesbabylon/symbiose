/**
 * Text parser/renderer with minimal markdown support
 *
 */
export class TextRendererClass {


    public static render(text:string) : string {
        return text;

        // #deprecated - after import in backend
        return text
        .replace(/  \* /gim, ' * ')                                         // cleanup list items notation (prevent double line return)
        .replace(/  ([0-9]{1,2}\.) /gim, ' ┌$1 ')                           // cleanup list items notation (prevent double line return)
        .replace(/  /gim, '┐')                                              // carriage return (convert to temp single char)
        .replace(/\*\*(.*?)\*\*/gim, '<b>$1</b>')                           // bold text
        .replace(/\* ([^\*┐]*)/gim, '<ul><li>$1</li></ul>')                 // list items
		.replace(/\*([^\*]*)\*/gim, '<em>$1</em>')                          // italic text
        .replace(/([0-9]{1,2})\. ([^┐┌]*)/gim, '<ol start="$1"><li>$2</li></ol>')   // list items
        .replace(/┌/gim, '')                                                // ol escape char
        .replace(/┐/gim, '<br />')                                          // carriage return
    }
}

export default TextRendererClass;