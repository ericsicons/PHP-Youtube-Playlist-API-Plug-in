<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html"/>
    <xsl:template match="/">
        <html>
            <body>
                <h2>Playlist Data</h2>
                <div style="text-align:left">ID : <xsl:value-of select="playlist/id"/></div>
                <div style="text-align:left">title : <xsl:value-of select="playlist/title"/></div>
                <div style="text-align:left">description : <xsl:value-of select="playlist/description"/></div>
                <div style="text-align:left">numVideos : <xsl:value-of select="playlist/numVideos"/></div>
                     
                <table border="1">
                    <tr>
                        <th>
                            id : 
                        </th>
                        <th>
                            title : 
                        </th>
                        <th>
                            duration : 
                        </th>
                        <th>
                            thumbnail : 
                        </th>
                        <th>
                            datePublished : 
                        </th>
                        <th>
                            description : 
                        </th>
                        <th>
                            views : 
                        </th>
                        <th>
                            favorites : 
                        </th>
                        <th>
                            numRated : 
                        </th>
                        <th>
                            author : 
                        </th>
                    </tr>
                    <xsl:for-each select="playlist/video">
                        <tr>
                            <td>
                                <xsl:value-of select="id"/>
                            </td>
                            <td>
                                <xsl:value-of select="title"/>
                            </td>
                            <td>
                                <xsl:value-of select="duration"/>
                            </td>
                            <td>
                                <xsl:value-of select="thumbnail"/>
                            </td>
                            <td>
                                <xsl:value-of select="datePublished"/>
                            </td>
                            <td>
                                <xsl:value-of select="description"/>
                            </td>
                            <td>
                                <xsl:value-of select="views"/>
                            </td>
                            <td>
                                <xsl:value-of select="favorites"/>
                            </td>
                            <td>
                                <xsl:value-of select="numRated"/>
                            </td>
                            <td>
                                <xsl:value-of select="author"/>
                            </td>
                        </tr>
                    </xsl:for-each>
                </table>
            </body>
        </html>
    </xsl:template>
</xsl:stylesheet>